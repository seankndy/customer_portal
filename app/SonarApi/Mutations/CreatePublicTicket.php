<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Mutations\Inputs\CreatePublicTicketMutationInput;
use App\SonarApi\Resources\Ticket;

class CreatePublicTicket extends BaseMutation
{
    public CreatePublicTicketMutationInput $input;

    public function __construct(CreatePublicTicketMutationInput $input)
    {
        $this->input = $input;
    }

     public function returnResource(): ?string
     {
         return Ticket::class;
     }

}