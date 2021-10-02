<?php

namespace App\SonarApi\Types;

abstract class Type
{
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function name()
    {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    public function __toString()
    {
        return '' . $this->value;
    }
}