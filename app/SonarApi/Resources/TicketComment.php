<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class TicketComment extends BaseResource
{
    public int $id;

    public string $body;

    public Carbon $createdAt;

    public Carbon $updatedAt;

    public User $user;
}