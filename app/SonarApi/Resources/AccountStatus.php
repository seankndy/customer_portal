<?php

namespace App\SonarApi\Resources;

class AccountStatus extends BaseResource
{
    public int $id;

    public string $name;

    public bool $activatesAccount;

    public static function fromJsonObject(object $jsonObject): self
    {
        return new self([
            'id' => $jsonObject->id,
            'name' => $jsonObject->name,
            'activatesAccount' => (bool)$jsonObject->activates_account,
        ]);
    }
}