<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class TicketReply extends BaseResource
{
    public int $id;

    public ?string $author;

    public ?string $authorEmail;

    public string $body;

    public Carbon $createdAt;

    public Carbon $updatedAt;

    public User $user;
}