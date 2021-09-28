<?php

namespace App\SonarApi\Resources;

class Company extends BaseResource
{
    public int $id;

    public string $name;

    public bool $default;
}