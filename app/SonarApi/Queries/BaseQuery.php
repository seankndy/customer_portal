<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Client;
use App\SonarApi\Exceptions\ResourceNotFoundException;
use App\SonarApi\Resources\BaseResource;
use GraphQL\QueryBuilder\QueryBuilder;
use Illuminate\Support\Collection;

abstract class BaseQuery implements Query
{
    protected ?string $sortBy = null;

    protected string $sortOrder = 'ASC';

    protected array $where = [
        'integer_fields' => [],
        'string_fields' => [],
    ];

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
     * @throws ResourceNotFoundException
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function get(): Collection
    {
        $response = $this->client->query($this);

        if (!$response->{$this->objectName()}->entities) {
            throw new ResourceNotFoundException("Resource(s) not found.");
        }

        return collect($response->{$this->objectName()}->entities)
            ->map(fn($entity) => ($this->resource())::fromJsonObject($entity));
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

    public function where(string $field, $value): self
    {
        if (is_array($value)) {
            $key = is_int($value[0]) ? 'integer_fields' : 'string_fields';

            $this->where[$key][$field] = \array_merge(
                $this->where[$key][$field] ?? [],
                $value
            );

            return $this;
        }

        $this->where[is_int($value) ? 'integer_fields' : 'string_fields'][$field][] = $value;

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

        return $variables;
    }

    protected function buildSearchFromWhere(): array
    {
        $data = [
            'string_fields' => [],
            'integer_fields' => [],
        ];

        foreach ($this->where as $type => $fieldValues) {
            foreach ($fieldValues as $field => $values) {
                foreach ($values as $value) {
                    if ($type == 'integer_fields') {
                        $search = [
                            'attribute' => $field,
                            'search_value' => $value,
                            'operator' => 'EQ',
                        ];
                    } else {
                        $search = [
                            'attribute' => $field,
                            'search_value' => $value,
                            'match' => true,
                        ];
                    }

                    $data[$type][] = $search;
                }
            }
        }

        return $data;
    }
}