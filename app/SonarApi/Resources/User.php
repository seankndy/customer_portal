<?php

namespace App\SonarApi\Resources;

class User extends BaseResource
{
    public int $id;

    public string $name;

    public string $username;

    public string $emailAddress;

    public bool $enabled;
}

