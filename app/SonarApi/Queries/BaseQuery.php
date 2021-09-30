<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Client;
use App\SonarApi\Resources\BaseResource;
use GraphQL\QueryBuilder\QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseQuery implements Query
{
    protected ?string $sortBy = null;

    protected string $sortOrder = 'ASC';

    protected array $where = [
        'integer_fields' => [],
        'string_fields' => [],
    ];

    protected bool $paginate = false;

    protected int $paginateCurrentPage;

    protected int $paginatePerPage;

    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Return resource class name.
     */
    abstract protected function resource(): string;

    /**
     * Return the query object name.
     */
    abstract protected function objectName(): string;

    /**
     * Execute the query.
     * @return Collection<int, BaseResource>
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function get(): Collection
    {
        $response = $this->client->query($this);

        return collect($response->{$this->objectName()}->entities)
            ->map(fn($entity) => ($this->resource())::fromJsonObject($entity));
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

        $response = $this->client->query($this);
        $pageInfo = $response->{$this->objectName()}->page_info;
        $entities = collect($response->{$this->objectName()}->entities)
            ->map(fn($entity) => ($this->resource())::fromJsonObject($entity));

        return new LengthAwarePaginator($entities, $pageInfo->total_count, $perPage, $currentPage, [
            'path' => $path,
        ]);
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

    public function query(): \GraphQL\Query
    {
        $queryBuilder = (new QueryBuilder($this->objectName()))
            ->selectField(($this->resource())::graphQLQuery(true)[0]);

        if ($this->where) {
            $queryBuilder->setVariable('search', 'Search')
                ->setArgument('search', ['$search']);
        }
        if ($this->sortBy) {
            $queryBuilder->setVariable('sorter', 'Sorter')
                ->setArgument('sorter', ['$sorter']);
        }
        if ($this->paginate) {
            $queryBuilder->setVariable('paginator', 'Paginator')
                ->setArgument('paginator', '$paginator')
                ->selectField(
                    (new \GraphQL\Query('page_info'))
                    ->setSelectionSet([
                        'records_per_page',
                        'page',
                        'total_count',
                    ])
                );
        }

        return $queryBuilder->getQuery();
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

    protected function buildSearchFromWhere(): array
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

        print_r($data);

        return $data;
    }
}