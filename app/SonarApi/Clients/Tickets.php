<?php

namespace App\SonarApi\Clients;

use App\SonarApi\Exceptions\ResourceNotFoundException;
use App\SonarApi\Resources\Account;
use App\SonarApi\Resources\Ticket;
use App\SonarApi\Resources\User;
use GraphQL\Query;
use GraphQL\Variable;
use Illuminate\Support\Collection;

class Tickets extends BaseClient
{
    /**
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws ResourceNotFoundException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     */
    public function get(int $id): Ticket
    {
        echo (string)            (new Query('tickets'))
            ->setArguments(['id' => $id])
            ->setSelectionSet(Ticket::graphQLQuery());
        $response = $this->client->query(
            (new Query('tickets'))
                ->setArguments(['id' => $id])
                ->setSelectionSet(Ticket::graphQLQuery())
        );

        if (!$response->tickets->entities) {
            throw new ResourceNotFoundException("Ticket with ID '$id' not found.");
        }

        return Ticket::fromJsonObject($response->tickets->entities[0]);
    }

    /**
     * @throws \App\SonarApi\Exceptions\SonarHttpException
     * @throws ResourceNotFoundException
     * @throws \App\SonarApi\Exceptions\SonarQueryException
     * @return Ticket[]
     */
    public function getMany(array $ids): array
    {
        $response = $this->client->query(
            (new Query('tickets'))
                ->setVariables([
                    new Variable('search', 'Search')
                ])
                ->setArguments(['search' => ['$search']])
                ->setSelectionSet(Ticket::graphQLQuery()),
            ['search' => [
                'integer_fields' => \array_map(
                    fn($id) => ['attribute' => 'id', 'operator' => 'EQ', 'search_value' => $id],
                    $ids
                ),
            ]]
        );

        if (!$response->tickets->entities) {
            throw new ResourceNotFoundException("Tickets not found.");
        }

        return \array_map(
            fn($entity) => Ticket::fromJsonObject($entity),
            $response->tickets->entities
        );
    }
}