<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;
use Spatie\DataTransferObject\DataTransferObject;

class TicketReplyData extends DataTransferObject
{
    public int $ticketId;

    public string $body;

    public string $author;

    public string $authorEmail;
}