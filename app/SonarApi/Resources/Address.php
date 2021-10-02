<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class Address extends BaseResource
{
    public int $id;

    public string $type;

    public string $line1;

    public ?string $line2;

    public string $city;

    public string $zip;

    public ?string $latitude;

    public ?string $longitude;

    public bool $serviceable;

    public Carbon $createdAt;

    public Carbon $updatedAt;
}