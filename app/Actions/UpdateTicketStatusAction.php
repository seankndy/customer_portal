<?php

namespace App\Actions;

use SeanKndy\SonarApi\Client;
use SeanKndy\SonarApi\Mutations\Inputs\InputBuilder;
use SeanKndy\SonarApi\Resources\Ticket;
use SeanKndy\SonarApi\Types\Int64Bit;

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

        return $this->sonarClient->mutations()->updateTicket([
            'id!' => $ticketId,
            'input' => fn(InputBuilder $input): InputBuilder => $input->type('UpdateTicketMutationInput')->data([
                'status' => $newStatus,
            ]),
        ])->return(Ticket::class)->run();
    }
}