<?php

namespace App\SonarApi\Resources;

class AccountStatus extends BaseResource
{
    public int $id;

    public string $name;

    public bool $activatesAccount;
}