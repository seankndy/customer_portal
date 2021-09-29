<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Mutations\Inputs\CreateTicketReplyMutationInput;
use App\SonarApi\Resources\TicketReply;

class CreateTicketReply extends BaseMutation
{
    public CreateTicketReplyMutationInput $input;

    public function __construct(CreateTicketReplyMutationInput $input)
    {
        $this->input = $input;
    }

     public function returnResource(): ?string
     {
         return TicketReply::class;
     }

}