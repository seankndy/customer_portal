<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class Ticket extends BaseResource
{
    public int $id;

    public ?int $parentTicketId;

    public int $ticketableId;

    public string $ticketableType;

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

    public TicketGroup $ticketGroup;

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

    protected array $with = [
        'ticketRecipients',
        'ticketGroup',
        'ticketCategories',
    ];

    public function latestReply(): ?TicketReply
    {
        return collect($this->ticketReplies)->sortByDesc(function ($reply) {
            return $reply->createdAt->getTimestamp();
        })->first();
    }

}