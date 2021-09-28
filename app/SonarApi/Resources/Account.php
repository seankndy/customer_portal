<?php

namespace App\SonarApi\Resources;

use Carbon\Carbon;

class Account extends BaseResource
{
    public int $id;

    public string $name;

    public AccountStatus $accountStatus;

    public Company $company;

    public Carbon $createdAt;

    public Carbon $updatedAt;

    /**
     * @var \App\SonarApi\Resources\Address[]
     */
    public array $addresses;

    public function physicalAddress(): ?Address
    {
        return $this->addresses->filter(fn($a) => $a->type === 'PHYSICAL')->first();
    }

    public function mailingAddress(): ?Address
    {
        return $this->addresses->filter(fn($a) => $a->type === 'MAILING')->first();
    }
}