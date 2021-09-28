<?php

namespace App\SonarApi;

class Reflection
{
    public static function getPublicProperties($objectOrClass): array
    {
        return (new \ReflectionClass($objectOrClass))
            ->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * Get property's type, wrap property type in array if the type is an array-of the type
     * @return array|string|null
     */
    public static function getPropertyType(\ReflectionProperty $property)
    {
        if ($docComment = $property->getDocComment()) {
            if (preg_match('/@var\s+(.+)/', $docComment, $m)) {
                $type = $m[1];
                if (substr($type, -2) == '[]') {
                    return [substr($type, 0, -2)];
                } else {
                    return $type;
                }
            }
        } else if (($type = $property->getType()) !== null) {
            return $type->getName();
        }
        return null;
   }
}
