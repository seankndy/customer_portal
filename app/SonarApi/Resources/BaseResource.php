<?php

namespace App\SonarApi\Resources;

use App\SonarApi\Reflection;
use Carbon\Carbon;
use GraphQL\Query;
use Illuminate\Support\Str;

abstract class BaseResource
{
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException("Property '$key' does not exist on Resource class " .
                    get_class($this));
            }
            $this->$key = $value;
        }
    }

    /**
     * Return new instance of resource from the JSON response object using reflection.  You may override this method
     * on a concrete resource if this doesn't suffice or for increased performance.
     */
    public static function fromJsonObject(object $jsonObject): self
    {
        $data = [];

        foreach (Reflection::getPublicProperties(static::class) as $property) {
            $jsonVar = Str::snake($property->getName());
            $propertyVar = $property->getName();

            if (property_exists($jsonObject, $jsonVar)) {
                $type = Reflection::getPropertyType($property);

                if (is_array($type)) {
                    $data[$propertyVar] = \array_map(
                        fn($entity) => ($type[0])::fromJsonObject($entity),
                        $jsonObject->$jsonVar->entities
                    );
                } else if (is_a($type, Carbon::class, true)) {
                    $data[$propertyVar] = $jsonObject->$jsonVar ? Carbon::createFromTimeString($jsonObject->$jsonVar) : null;
                } else if (is_subclass_of($type, BaseResource::class)) {
                    $data[$propertyVar] = $type::fromJsonObject($jsonObject->$jsonVar);
                } else {
                    $data[$propertyVar] = $jsonObject->$jsonVar;
                }
            }
        }

        return new static($data);
    }

    /**
     * Create GraphQL Query based on the property types of the resource class.
     * Override this if a resource's properties do not map directly to Sonar fields
     * by simply converting camel to snake case.
     */
    public static function graphQLQuery($wrapInEntities = true): array
    {
        $vars = [];
        foreach (Reflection::getPublicProperties(static::class) as $property) {
            $type = Reflection::getPropertyType($property);
            if (is_array($type)) {
                $type = $type[0];
                $array = true;
            } else {
                $array = false;
            }

            if (is_subclass_of($type, self::class)) {
                if ($array) {
                    $vars[] = (new Query(Str::snake($property->getName())))
                        ->setSelectionSet($type::graphQLQuery(true));
                } else {
                    $classBaseName = \substr($type, \strrpos($type, '\\')+1);
                    $vars[] = (new Query(Str::snake(\lcfirst($classBaseName))))
                        ->setSelectionSet($type::graphQLQuery(false));
                }
            } else {
                $vars[] = Str::snake($property->getName());
            }
        }

        return $wrapInEntities ? [(new Query('entities'))->setSelectionSet($vars)] : $vars;
    }
}