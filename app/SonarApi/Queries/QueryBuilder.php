<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Client;
use App\SonarApi\Resources\BaseResource;
use GraphQL\RawObject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QueryBuilder
{
    private Client $client;

    private string $resource;

    private string $objectName;

    private array $with = [];

    private ?string $sortBy = null;

    private string $sortOrder = 'ASC';

    private array $where = [
        'integer_fields' => [],
        'string_fields' => [],
    ];

    private bool $paginate = false;

    private int $paginateCurrentPage;

    private int $paginatePerPage;

    public function __construct(
        Client $client,
        string $resourceClass,
        string $objectName
    ) {
        if (!is_subclass_of($resourceClass, BaseResource::class)) {
            throw new \InvalidArgumentException("\$resourceClass must by subclass of ".BaseResource::class);
        }

        $this->client = $client;
        $this->resource = $resourceClass;
        $this->objectName = $objectName;
    }

    /**
     * Execute the query.
     * @return Collection<int, BaseResource>
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function get(): Collection
    {
        echo (string)$this->getQuery()->query();
        print_r($this->getQuery()->variables());
        $response = $this->client->query($this->getQuery());

        return collect($response->{$this->objectName}->entities)
            ->map(fn($entity) => ($this->resource)::fromJsonObject($entity));
    }

    /**
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function first()
    {
        return $this->get()->first();
    }

    /**
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function paginate(int $perPage = 25, int $currentPage = 1, string $path = '/'): LengthAwarePaginator
    {
        $this->paginate = true;
        $this->paginatePerPage = $perPage;
        $this->paginateCurrentPage = $currentPage;

        $response = $this->client->query($this->getQuery());
        $pageInfo = $response->{$this->objectName}->page_info;
        $entities = collect($response->{$this->objectName}->entities)
            ->map(fn($entity) => ($this->resource)::fromJsonObject($entity));

        return new LengthAwarePaginator($entities, $pageInfo->total_count, $perPage, $currentPage, [
            'path' => $path,
        ]);
    }

    public function with(...$args): self
    {
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $this->with = \array_merge($this->with, $arg);
            } else {
                $this->with[$arg] = 0;
            }
        }

        return $this;
    }

    public function sortBy(string $sortBy, string $sortOrder = 'ASC'): self
    {
        $this->sortBy = $sortBy;
        $this->sortOrder($sortOrder);

        return $this;
    }

    public function sortOrder(string $sortOrder): self
    {
        $this->sortOrder = \strtoupper($sortOrder);

        return $this;
    }

    public function where(string $field, ...$args): self
    {
        if (count($args) == 1) {
            $operator = '=';
            $value = $args[0];
        } else if (count($args) == 2) {
            $operator = $args[0];
            $value = $args[1];
        } else {
            throw new \InvalidArgumentException("Minimum of 2 arguments, maximum of 3");
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        switch (gettype($value[0])) {
            case 'integer':
                $fieldType = 'integer_fields';
                break;
            case 'boolean':
                if ($operator != '=') {
                    throw new \InvalidArgumentException("Boolean values only support an equality (=) comparison.");
                }
                $fieldType = 'boolean_fields';
                break;
            case 'NULL':
                $fieldType = $operator == '=' ? 'unset_fields' : 'exists';
                break;
            default:
                $fieldType = 'string_fields';
                break;
        }

        if ($fieldType == 'boolean_fields' && $operator != '=') {
            throw new \InvalidArgumentException("Boolean values only support an equality (=) comparison.");
        }

        $this->where[$fieldType][$field] = \array_merge(
            $this->where[$fieldType][$field] ?? [],
            [$value, $operator]
        );

        return $this;
    }

    public function getQuery($many = true): Query
    {
        $queryBuilder = new \GraphQL\QueryBuilder\QueryBuilder($this->objectName);

        $variables = [];
        $manySelectionSet = [];
        foreach ($this->resource::fieldsAndTypes() as $field => $type) {
            if (is_array($type)) {
                $type = $type[0];
                $manyRelation = true;
            } else {
                $manyRelation = false;
            }

            if (is_subclass_of($type, BaseResource::class)) {
                if (!isset($this->with[$field])) {
                    continue;
                }

                $relationQueryBuilder = new self($this->client, $type, $field);
                if (is_callable($this->with[$field])) {
                    ($this->with[$field])($relationQueryBuilder);
                }

                $relationQuery = $relationQueryBuilder->getQuery($manyRelation);
                $select = $relationQuery->query();
                $variables = \array_merge($variables, $relationQuery->variables());
            } else {
                $select = $field;
            }

            if ($many) {
                $manySelectionSet[] = $select;
            } else {
                $queryBuilder->selectField($select);
            }
        }

        if ($manySelectionSet) {
            $queryBuilder->selectField(
                (new \GraphQL\Query('entities'))->setSelectionSet($manySelectionSet)
            );
        }

        if ($this->where) {
            // TODO: these variables dont come in if theyre coming from nested queries.
            $queryBuilder->setVariable($this->objectName.'_search', 'Search')
                ->setArgument('search', ['$'.$this->objectName.'_search']);

            $variables[$this->objectName.'_search'] = $this->buildSearchFromWhere();
        }
        if ($this->sortBy) {
            $queryBuilder->setVariable($this->objectName.'_sorter', 'Sorter')
                ->setArgument('sorter', ['$'.$this->objectName.'_sorter']);

            $variables[$this->objectName.'_sorter'] = [
                'attribute' => $this->sortBy,
                'direction' => $this->sortOrder,
            ];
        }
        if ($this->paginate) {
            $queryBuilder->setVariable($this->objectName.'_paginator', 'Paginator')
                ->setArgument('paginator', '$'.$this->objectName.'_paginator')
                ->selectField(
                    (new \GraphQL\Query('page_info'))
                    ->setSelectionSet([
                        'records_per_page',
                        'page',
                        'total_count',
                    ])
                );

            $variables[$this->objectName.'_paginator'] = [
                'page' => $this->paginateCurrentPage,
                'records_per_page' => $this->paginatePerPage,
            ];
        }

        return new class($queryBuilder, $variables) implements Query {
            public function __construct(
                \GraphQL\QueryBuilder\QueryBuilder $queryBuilder,
                array $variables
            ) {
                $this->queryBuilder = $queryBuilder;
                $this->variables = $variables;
            }
            public function query(): \GraphQL\Query {
                return $this->queryBuilder->getQuery();
            }
            public function variables(): array {
                return $this->variables;
            }
        };
    }

    public function variables(): array
    {
        $variables = [];

        if ($this->where) {
            $variables['search'] = $this->buildSearchFromWhere();
        }

        if ($this->sortBy) {
            $variables['sorter'] = [
                'attribute' => $this->sortBy,
                'direction' => $this->sortOrder,
            ];
        }

        if ($this->paginate) {
            $variables['paginator'] = [
                'page' => $this->paginateCurrentPage,
                'records_per_page' => $this->paginatePerPage,
            ];
        }

        return $variables;
    }

    private function buildSearchFromWhere(): array
    {
        $data = [
            'integer_fields' => [],
            'boolean_fields' => [],
            'string_fields' => [],
            'unset_fields' => [],
            'exists' => [],
        ];

        foreach ($this->where as $type => $fieldValues) {
            foreach ($fieldValues as $field => $valuesAndOperator) {
                [$values, $operator] = $valuesAndOperator;

                foreach ($values as $value) {
                    if ($type == 'integer_fields') {
                        $data[$type][] = [
                            'attribute' => $field,
                            'search_value' => $value,
                            'operator' => [
                                '=' => 'EQ',
                                '!=' => 'NEQ',
                            ][$operator]
                        ];
                    } else if ($type == 'boolean_fields') {
                        $data[$type][] = [
                            'attribute' => $field,
                            'search_value' => $value,
                        ];
                    } else if ($type == 'string_fields') {
                        $data[$type][] = [
                            'attribute' => $field,
                            'search_value' => $value,
                            'match' => $operator == '=',
                            'partial_matching' => false,
                        ];
                    } else if ($type == 'unset_fields' || $type == 'exists') {
                        $data[$type][] = $field;
                    }
                }
            }
        }

        return $data;
    }
}