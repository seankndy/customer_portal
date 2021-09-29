<?php

namespace App\Actions;

use App\DataTransferObjects\TicketReplyData;
use App\SonarApi\Client;
use App\SonarApi\Mutations\CreateTicketReply;
use App\SonarApi\Mutations\Inputs\CreateTicketReplyMutationInput;
use App\SonarApi\Resources\TicketReply;

class CreateTicketReplyAction
{
    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }
    
    public function __invoke(TicketReplyData $ticketReplyData): TicketReply
    {
        return $this->sonarClient->mutations()->run(
            new CreateTicketReply(
                new CreateTicketReplyMutationInput([
                    'ticketId' => $ticketReplyData->ticketId,
                    'body' => $ticketReplyData->body,
                    'incoming' => true,
                    'author' => $ticketReplyData->author,
                    'authorEmail' => $ticketReplyData->authorEmail,
                ])
            )
        );
    }
}