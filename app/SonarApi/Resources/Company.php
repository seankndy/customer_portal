<?php

namespace App\SonarApi\Resources;

class Company extends BaseResource
{
    public int $id;

    public string $name;

    public bool $default;

    public static function fromJsonObject(object $jsonObject): self
    {
        return new self([
            'id' => $jsonObject->id,
            'name' => $jsonObject->name,
            'default' => (bool)$jsonObject->default,
        ]);
    }
}