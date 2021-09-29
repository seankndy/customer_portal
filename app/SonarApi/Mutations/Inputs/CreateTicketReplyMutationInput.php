<?php

namespace App\SonarApi\Mutations\Inputs;

class CreateTicketReplyMutationInput extends BaseInput
{
    public int $ticketId;

    public ?string $body;

    public bool $incoming;

    public string $author;

    public string $authorEmail;
}