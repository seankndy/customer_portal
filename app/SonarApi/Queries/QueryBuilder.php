<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Client;
use App\SonarApi\Resources\BaseResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryBuilder
{
    /**
     * The resource class this QueryBuilder is for.
     */
    private string $resource;
    /**
     * The GraphQL query entity name.
     */
    private string $objectName;
    /**
     * Relations to include.
     */
    private array $with = [];
    /**
     * If this query will return an array of resources.
     */
    private bool $many = true;
    /**
     * If a QueryBuilder
     */
    private ?self $parentQueryBuilder;
    /**
     * Sorting field.
     */
    private ?string $sortBy = null;
    /**
     * Sorting direction.
     */
    private string $sortOrder = 'ASC';
    /**
     * Search criteria.
     */
    private array $where = [];
    /**
     * Should we paginate?
     */
    private bool $paginate = false;
    /**
     * Current pagination page.
     */
    private int $paginateCurrentPage;
    /**
     * Results per page.
     */
    private int $paginatePerPage;
    /**
     * Variables declared for the root QueryBuilder.
     * Only the root QueryBuilder should have this populated.
     */
    private array $declaredVariables = [];
    /**
     * $this->resource's fields (properties) and types gotten via Reflection.
     */
    private array $resourceFieldsAndTypes;
    /**
     * Sonar API Client, Client is required if the user code wants to call get(), first() or paginate().
     */
    private ?Client $client = null;

    public function __construct(
        string $resourceClass,
        string $objectName,
        self $parentQueryBuilder = null
    ) {
        if (!is_subclass_of($resourceClass, BaseResource::class)) {
            throw new \InvalidArgumentException("\$resourceClass must by subclass of ".BaseResource::class);
        }

        $this->resource = $resourceClass;
        $this->resourceFieldsAndTypes = $this->resource::fieldsAndTypes();
        $this->objectName = $objectName;
        $this->parentQueryBuilder = $parentQueryBuilder;
        $this->with((new $resourceClass())->with());
    }

    /**
     * Execute the query return result(s).
     *
     * @return BaseResource|Collection<int, BaseResource>|null
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function get()
    {
        if (!$this->client) {
            throw new \Exception("Cannot call get() without a client!");
        }

        $response = $this->client->query($this->getQuery());

        if (!$this->many) {
            return $response->{$this->objectName}
                ? ($this->resource)::fromJsonObject($response->{$this->objectName})
                : null;
        }
        return collect($response->{$this->objectName}->entities)
            ->map(fn($entity) => ($this->resource)::fromJsonObject($entity));
    }

    /**
     * Shortcut for getting first item from set of results.
     *
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     * @throws \Exception
     */
    public function first()
    {
        if (!$this->many) {
            throw new \Exception("first() cannot be called because of singular query.");
        }
        return $this->get()->first();
    }

    /**
     * Get and paginate the results.  Pagination is done server-side with Sonar's GraphQL paginator.
     *
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function paginate(int $perPage = 25, int $currentPage = 1, string $path = '/'): LengthAwarePaginator
    {
        if (!$this->many) {
            throw new \Exception("paginate() cannot be called because of singular query.");
        }

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

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Set if this query will return an array of resources.
     */
    public function many(bool $many): self
    {
        $this->many = $many;

        return $this;
    }

    /**
     * Specify relations to include in query.  You may pass array with key being relation and value a closure which
     * receives a QueryBuilder instance for the relation.
     */
    public function with(...$args): self
    {
        foreach ($args as $arg) {
            if (!is_array($arg)) {
                $arg = [$arg => 0];
            }

            foreach ($arg as $relation => $closure) {
                if (is_int($relation)) {
                    $relation = $closure;
                    $closure = 0;
                }

                $relationField = Str::snake($relation);

                if (!isset($this->resourceFieldsAndTypes[$relationField])
                    || !$this->resourceFieldsAndTypes[$relationField]->isResource()) {
                    throw new \InvalidArgumentException("Relation specified ($relation) is not a valid resource.");
                }

                $relationQueryBuilder = (new self(
                    $this->resourceFieldsAndTypes[$relationField]->type(),
                    $relationField,
                    $this
                ))->many($this->resourceFieldsAndTypes[$relationField]->arrayOf());

                if (is_callable($closure)) {
                    $closure($relationQueryBuilder);
                }

                $this->with[$relation] = $relationQueryBuilder;
            }
        }

        return $this;
    }

    /**
     * Set sorting field and optionally direction.
     */
    public function sortBy(string $sortBy, string $sortOrder = 'ASC'): self
    {
        $this->sortBy = Str::snake($sortBy);
        $this->sortOrder($sortOrder);

        return $this;
    }

    /**
     * Set sorting direction.
     */
    public function sortOrder(string $sortOrder): self
    {
        $this->sortOrder = \strtoupper($sortOrder);

        return $this;
    }

    /**
     * Specify a search filter criteria.
     */
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

        if (!isset($this->where[$fieldType])) {
            $this->where[$fieldType] = [];
        }

        $this->where[$fieldType][$field] = \array_merge(
            $this->where[$fieldType][$field] ?? [],
            [$value, $operator]
        );

        return $this;
    }

    /**
     * Get Query instance suitable for execution by Client.
     */
    public function getQuery(): Query
    {
        $queryBuilder = new \GraphQL\QueryBuilder\QueryBuilder($this->objectName);

        $variables = [];
        $manySelectionSet = [];
        foreach ($this->resourceFieldsAndTypes as $field => $type) {
            if ($type->isResource()) {
                $relationName = Str::camel($field);

                if (!isset($this->with[$relationName])) {
                    continue;
                }

                $relationQuery = $this->with[$relationName]->getQuery();
                $select = $relationQuery->query();
                $variables = \array_merge($variables, $relationQuery->variables());
            } else {
                $select = $field;
            }

            if ($this->many) {
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
            $this->declareVariable($this->objectName.'_search', 'Search');

            $queryBuilder->setArgument('search', ['$'.$this->objectName.'_search']);

            $variables[$this->objectName.'_search'] = $this->buildSearchFromWhere();
        }
        if ($this->sortBy) {
            $this->declareVariable($this->objectName.'_sorter', 'Sorter');

            $queryBuilder->setArgument('sorter', ['$'.$this->objectName.'_sorter']);

            $variables[$this->objectName.'_sorter'] = [
                'attribute' => $this->sortBy,
                'direction' => $this->sortOrder,
            ];
        }
        if ($this->paginate) {
            $this->declareVariable($this->objectName.'_paginator', 'Paginator');

            $queryBuilder
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

        if ($this->isRoot()) {
            foreach ($this->declaredVariables as $declaredVariable) {
                $queryBuilder->setVariable(...$declaredVariable);
            }
        }

        return new Query($queryBuilder->getQuery(), $variables);
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
                $field = Str::snake($field);

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

    /**
     * Declare variable on the root query builder as that is where variable declarations belong in GraphQL.
     */
    private function declareVariable(string $name, string $type, bool $isRequired = false, $defaultValue = null): self
    {
        if ($this->parentQueryBuilder) {
            $this->getRootQueryBuilder()->declareVariable($name, $type, $isRequired, $defaultValue);
        } else {
            $this->declaredVariables[] = [$name, $type, $isRequired, $defaultValue];
        }

        return $this;
    }

    private function getRootQueryBuilder()
    {
        if ($this->isRoot()) {
            return $this;
        }

        return $this->parentQueryBuilder->getRootQueryBuilder();
    }

    private function isRoot()
    {
        return $this->parentQueryBuilder === null;
    }
}