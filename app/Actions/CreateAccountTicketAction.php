<?php

namespace App\Actions;

use App\DataTransferObjects\AccountTicketData;
use SeanKndy\SonarApi\Client;
use SeanKndy\SonarApi\Mutations\Inputs\InputBuilder;
use SeanKndy\SonarApi\Resources\Ticket;

class CreateAccountTicketAction
{
    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }
    
    public function __invoke(AccountTicketData $ticketData): Ticket
    {
        return $this->sonarClient->mutations()->createPublicTicket([
            'input' => fn(InputBuilder $input): InputBuilder => $input->type('CreatePublicTicketMutationInput')->data([
                'subject' => $ticketData->subject,
                'status' => 'OPEN',
                'priority' => [
                    4 => 'CRITICAL',
                    3 => 'HIGH',
                    2 => 'MEDIUM',
                    1 => 'LOW',
                ][config('customer_portal.ticket_priority', 1)],
                'description' => 'Created via customer portal.',
                'ticketableId' => $ticketData->accountId,
                'ticketableType' => 'Account',
                'inboundMailboxId' => config('customer_portal.inbound_email_account_id'),
                'ticketGroupId' => config('customer_portal.ticket_group_id'),
            ])
        ])->return(Ticket::class)->run();
    }
}