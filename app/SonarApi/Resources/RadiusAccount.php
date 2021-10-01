<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class RadiusAccount extends BaseResource
{
    public int $id;

    public int $accountId;

    public string $username;

    public string $password;

    public Carbon $createdAt;

    public Carbon $updatedAt;
}