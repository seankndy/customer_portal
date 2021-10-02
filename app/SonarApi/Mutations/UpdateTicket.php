<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Mutations\Inputs\CreatePublicTicketMutationInput;
use App\SonarApi\Mutations\Inputs\UpdateTicketMutationInput;
use App\SonarApi\Resources\Ticket;
use App\SonarApi\Types\Int64Bit;

class UpdateTicket extends BaseMutation
{
    public Int64Bit $id;

    public UpdateTicketMutationInput $input;

    public function __construct(Int64Bit $id, UpdateTicketMutationInput $input)
    {
        $this->id = $id;
        $this->input = $input;
    }

     public function returnResource(): ?string
     {
         return Ticket::class;
     }

}