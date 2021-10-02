<?php

namespace App\Actions;

use App\SonarApi\Client;
use App\SonarApi\Mutations\Inputs\UpdateTicketMutationInput;
use App\SonarApi\Mutations\UpdateTicket;
use App\SonarApi\Resources\Ticket;
use App\SonarApi\Types\Int64Bit;

class UpdateTicketStatusAction
{
    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }

    /**
     * @param Ticket|int $ticket
     * @throws \Exception
     */
    public function __invoke($ticket, string $newStatus): Ticket
    {
        $ticketId = new Int64Bit($ticket instanceof Ticket ? $ticket->id : $ticket);

        return $this->sonarClient->mutations()->run(
            new UpdateTicket(
                $ticketId,
                new UpdateTicketMutationInput([
                    'status' => $newStatus,
                ])
            )
        );
    }
}