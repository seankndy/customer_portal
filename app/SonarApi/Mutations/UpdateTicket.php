<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Mutations\Inputs\CreatePublicTicketMutationInput;
use App\SonarApi\Mutations\Inputs\UpdateTicketMutationInput;
use App\SonarApi\Resources\Ticket;

class UpdateTicket extends BaseMutation
{
    public int $id;

    public UpdateTicketMutationInput $input;

    public function __construct(int $id, UpdateTicketMutationInput $input)
    {
        $this->id = $id;
        $this->input = $input;
    }

     public function returnResource(): ?string
     {
         return Ticket::class;
     }

}