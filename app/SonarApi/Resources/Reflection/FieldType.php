<?php

namespace App\SonarApi\Resources\Reflection;

use App\SonarApi\Resources\BaseResource;

class FieldType
{
    private string $type;

    private bool $arrayOf;

    public function __construct(string $type, bool $arrayOf)
    {
        $this->type = $type;
        $this->arrayOf = $arrayOf;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function arrayOf(): bool
    {
        return $this->arrayOf;
    }

    public function isResource()
    {
        return is_subclass_of($this->type, BaseResource::class);
    }
}