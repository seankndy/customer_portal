<?php

namespace App\Actions;

use App\DataTransferObjects\AccountTicketData;
use App\DataTransferObjects\TicketReplyData;
use App\SonarApi\Client;
use App\SonarApi\Mutations\CreatePublicTicket;
use App\SonarApi\Mutations\Inputs\CreatePublicTicketMutationInput;
use App\SonarApi\Mutations\Inputs\UpdateTicketMutationInput;
use App\SonarApi\Mutations\UpdateTicket;
use App\SonarApi\Resources\Ticket;

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
        $ticketId = $ticket instanceof Ticket ? $ticket->id : $ticket;

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