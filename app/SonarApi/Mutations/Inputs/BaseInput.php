<?php

namespace App\SonarApi\Mutations\Inputs;

use Illuminate\Support\Str;

abstract class BaseInput implements Input
{
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException("Property '$key' does not exist on mutation input class " .
                    get_class($this));
            }
            $this->$key = $value;
        }
    }

    /**
     * Returns the base class name by default, may be overidden.
     */
    public function typeName(): string
    {
        return \substr(static::class, \strrpos(static::class, '\\')+1);
    }

    public function toArray(): array
    {
        $vars = [];
        foreach (\get_object_vars($this) as $var => $value) {
            $vars[Str::snake($var)] = $value;
        }
        return $vars;
    }
}