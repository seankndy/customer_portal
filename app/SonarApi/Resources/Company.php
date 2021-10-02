<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class Company extends BaseResource
{
    public int $id;

    public string $name;

    public bool $default;

    public Carbon $createdAt;

    public Carbon $updatedAt;
}