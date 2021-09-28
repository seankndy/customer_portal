<?php

namespace App\SonarApi\Clients;

use App\SonarApi\Exceptions\ResourceNotFoundException;
use App\SonarApi\Resources\Account;
use GraphQL\Query;
use GraphQL\Variable;
use Illuminate\Support\Collection;

class Accounts extends BaseClient
{
    /**
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws ResourceNotFoundException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function get(int $id): Account
    {
        $response = $this->client->query(
            (new Query('accounts'))
                ->setArguments(['id' => $id])
                ->setSelectionSet(Account::graphQLQuery())
        );

        if (!$response->accounts->entities) {
            throw new ResourceNotFoundException("Account with ID '$id' not found.");
        }

        return Account::fromJsonObject($response->accounts->entities[0]);
    }

    /**
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws ResourceNotFoundException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     * @return Collection<int, Account>
     */
    public function getMany(array $ids): Collection
    {
        $response = $this->client->query(
            (new Query('accounts'))
                ->setVariables([
                    new Variable('search', 'Search')
                ])
                ->setArguments(['search' => ['$search']])
                ->setSelectionSet(Account::graphQLQuery()),
            ['search' => [
                'integer_fields' => \array_map(
                    fn($id) => ['attribute' => 'id', 'operator' => 'EQ', 'search_value' => $id],
                    $ids
                ),
            ]]
        );

        if (!$response->accounts->entities) {
            throw new ResourceNotFoundException("Accounts not found.");
        }

        return collect(\array_map(
            fn($entity) => Account::fromJsonObject($entity),
            $response->accounts->entities
        ));
    }
}