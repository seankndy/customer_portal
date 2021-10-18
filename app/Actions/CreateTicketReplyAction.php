<?php

namespace App\Actions;

use App\DataTransferObjects\TicketReplyData;
use SeanKndy\SonarApi\Client;
use SeanKndy\SonarApi\Mutations\Inputs\InputBuilder;
use SeanKndy\SonarApi\Resources\TicketReply;

class CreateTicketReplyAction
{
    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }
    
    public function __invoke(TicketReplyData $ticketReplyData): TicketReply
    {
        return $this->sonarClient->mutations()->createTicketReply([
            'input' => fn(InputBuilder $input): InputBuilder => $input->type('CreateTicketReplyMutationInput')->data([
                'ticketId' => $ticketReplyData->ticket->id,
                'body' => $ticketReplyData->body,
                'incoming' => true,
                'author' => $ticketReplyData->author,
                'authorEmail' => $ticketReplyData->authorEmail,
            ])
        ])->return(TicketReply::class)->run();
    }
}