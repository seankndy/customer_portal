<?php

namespace App\SonarApi;

class Reflection
{
    /**
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
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

    /**
     * Get property's meta data key/value pairs
     */
    public static function getPropertyMeta(\ReflectionProperty $property): array
    {
        $meta = [];
        if ($docComment = $property->getDocComment()) {
            if (preg_match_all('/@meta\s+(.+?)\s+(.+)/', $docComment, $m)) {
                foreach ($m[1] as $i => $key) {
                    $meta[$key] = $m[2][$i];
                }
            }
        }
        return $meta;
    }
}
