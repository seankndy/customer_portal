<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class Ticket extends BaseResource
{
    public int $id;

    public ?int $parentTicketId;

    public string $status;

    public string $description;

    public string $subject;

    public ?Carbon $closedAt;

    public ?Carbon $dueDate;

    public Carbon $createdAt;

    public Carbon $updatedAt;

    /**
     * @var \App\SonarApi\Resources\TicketCategory[]
     */
    public array $ticketCategories;

    public TicketGroup $ticketGroups;

    /**
     * @var \App\SonarApi\Resources\TicketRecipient[]
     */
    public array $ticketRecipients;

    /**
     * @var \App\SonarApi\Resources\TicketReply[]
     */
    public array $ticketReplies;

    /**
     * @var \App\SonarApi\Resources\TicketComment[]
     */
    public array $ticketComments;
}