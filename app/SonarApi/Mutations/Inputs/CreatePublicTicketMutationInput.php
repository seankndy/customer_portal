<?php

namespace App\SonarApi\Mutations\Inputs;

use Carbon\Carbon;

class CreatePublicTicketMutationInput extends BaseInput
{
    public ?string $subject;

    public ?string $description;

    public string $status;

    public string $priority;

    public ?Carbon $dueDate;

    public int $ticketableId;

    public string $ticketableType;

    public ?int $parentTicketId;

    public ?int $ticketGroupId;

    public int $inboundMailboxId;

}