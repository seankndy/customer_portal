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

    /**
     * @var \App\SonarApi\Resources\RadiusAccount[]
     */
    public array $radiusAccounts;

    protected array $with = ['accountStatus', 'company'];

    public function physicalAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->type === 'PHYSICAL') {
                return $address;
            }
        }
        return null;
    }

    public function mailingAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->type === 'MAILING') {
                return $address;
            }
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->name .
            ($this->physicalAddress()
                ? " - " . $this->physicalAddress()->line1 . ', ' . $this->physicalAddress()->line2 . ', ' .
                    $this->physicalAddress()->city . ' ' . $this->physicalAddress()->zip
                : ''
            );
    }
}