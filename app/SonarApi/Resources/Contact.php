<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class Contact extends BaseResource
{
    public int $id;

    public int $contactableId;

    public string $contactableType;

    public ?string $name;

    public ?string $username;

    public ?string $emailAddress;

    public ?string $role;

    public bool $primary;

    public Carbon $createdAt;

    public Carbon $updatedAt;
}