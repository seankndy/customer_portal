<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Client;
use App\SonarApi\Queries\Search\Search;
use App\SonarApi\Resources\Reflection\Reflection;
use App\SonarApi\Resources\ResourceInterface;
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
     * Only select these fields.
     */
    private array $only = [];
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
    private ?Search $search = null;
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
        if (!is_a($resourceClass, ResourceInterface::class, true)) {
            throw new \InvalidArgumentException("\$resourceClass must implement ".ResourceInterface::class);
        }

        $this->resource = $resourceClass;
        $this->resourceFieldsAndTypes = Reflection::getResourceProperties($this->resource);
        $this->objectName = $objectName;
        $this->parentQueryBuilder = $parentQueryBuilder;
        $this->with((new $resourceClass())->with());
    }

    /**
     * Execute the query return result(s).
     *
     * @return ResourceInterace|Collection<int, ResourceInterface>|null
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

                if (!isset($this->resourceFieldsAndTypes[$relation])
                    || !$this->resourceFieldsAndTypes[$relation]->isResource()) {
                    throw new \InvalidArgumentException("Relation specified ($relation) is not a valid resource.");
                }

                $relationQueryBuilder = (new self(
                    $this->resourceFieldsAndTypes[$relation]->type(),
                    Str::snake($relation),
                    $this
                ))->many($this->resourceFieldsAndTypes[$relation]->arrayOf());

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
     * Limit the fields returned by query to only these fields.
     */
    public function only(...$args): self
    {
        foreach ($args as $arg) {
            if (!is_array($arg)) {
                $arg = [$arg];
            }

            foreach ($arg as $field) {
                if (!in_array($field, $this->only)) {
                    $this->only[] = $field;
                }
            }
        }

        return $this;
    }

    /**
     * Specify a search filter criteria.
     */
    public function where(string $field, ...$args): self
    {
        if (!isset($this->search)) {
            $this->search = new Search();
        }

        $this->search->where(Str::snake($field), ...$args);

        return $this;
    }

    /**
     * Specify an OR search filter criteria.
     */
    public function orWhere(string $field, ...$args): self
    {
        if (!isset($this->search)) {
            $this->search = new Search();
        }

        $this->search->orWhere(Str::snake($field), ...$args);

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
            if ($this->only && !\in_array($field, $this->only)) {
                continue;
            }

            if ($type->isResource()) {
                if (!isset($this->with[$field])) {
                    continue;
                }

                $relationQuery = $this->with[$field]->getQuery();
                $select = $relationQuery->query();
                $variables = \array_merge($variables, $relationQuery->variables());
            } else {
                $select = Str::snake($field);
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

        if ($this->search) {
            $this->declareVariable($this->objectName.'_search', '[Search]');

            $queryBuilder->setArgument('search', '$'.$this->objectName.'_search');

            $variables[$this->objectName.'_search'] = $this->search->toArray();
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