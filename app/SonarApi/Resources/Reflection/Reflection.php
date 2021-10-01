<?php

namespace App\SonarApi\Resources\Reflection;

class Reflection
{
    /**
     * @throws \ReflectionException
     */
    public static function getResourceFields($objectOrClass): array
    {
        $properties = (new \ReflectionClass($objectOrClass))
            ->getProperties(\ReflectionProperty::IS_PUBLIC);

        $fields = [];
        foreach ($properties as $property) {
            $fields[$property->getName()] = self::getFieldType($property);
        }
        return $fields;
    }

    public static function getFieldType(\ReflectionProperty $property): FieldType
    {
        $type = null;
        $isArray = false;
        if ($docComment = $property->getDocComment()) {
            if (preg_match('/@var\s+(.+)/', $docComment, $m)) {
                $type = $m[1];
                if (substr($type, -2) == '[]') {
                    $type = substr($type, 0, -2);
                    $isArray = true;
                }
            }
        } else if (($type = $property->getType()) !== null) {
            $type = $type->getName();
        }

        return new FieldType($type, $isArray);
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
