<?php

namespace App\DataTransferObjects;

use SeanKndy\SonarApi\Resources\Ticket;
use Illuminate\Http\Request;
use Spatie\DataTransferObject\DataTransferObject;

class TicketReplyData extends DataTransferObject
{
    public Ticket $ticket;

    public string $body;

    public string $author;

    public string $authorEmail;
}