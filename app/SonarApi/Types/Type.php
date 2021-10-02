<?php

namespace App\SonarApi\Types;

abstract class Type
{
    /**
     * @var mixed
     */
    public $value;

    public function name()
    {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    public function __toString()
    {
        return '' . $this->value;
    }
}