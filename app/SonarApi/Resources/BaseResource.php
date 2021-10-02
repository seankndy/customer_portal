<?php

namespace App\SonarApi\Resources;

use App\SonarApi\Queries\QueryBuilder;
use App\SonarApi\Resources\Reflection\Reflection;
use App\SonarApi\Types\Type;
use Carbon\Carbon;
use Illuminate\Support\Str;

abstract class BaseResource
{
    protected array $with = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException("Property '$key' does not exist on Resource class " .
                    get_class($this));
            }
            $this->$key = $value;
        }
    }

    public function with(): array
    {
        $with = [];
        foreach ($this->with as $key => $value) {
            if (is_int($key)) {
                $with[$value] = 0;
            } else {
                $with[$key] = $value;
            }
        }
        return $with;
    }

    /**
     * Return new instance of resource from the JSON response object using reflection.  You may override this method
     * on a concrete resource if this doesn't suffice or for increased performance.
     * @throws \Exception
     */
    public static function fromJsonObject(object $jsonObject): self
    {
        $data = [];

        foreach (Reflection::getResourceFields(static::class) as $field => $type) {
            $jsonVar = Str::snake($field);

            if (property_exists($jsonObject, $jsonVar)) {
                if ($type->arrayOf()) {
                    $data[$field] = \array_map(
                        fn($entity) => ($type->type())::fromJsonObject($entity),
                        $jsonObject->$jsonVar->entities
                    );
                } else if (is_a($type->type(), Carbon::class, true)) {
                    $data[$field] = $jsonObject->$jsonVar ? Carbon::createFromTimeString($jsonObject->$jsonVar) : null;
                } else if (is_a($type->type(), \DateTime::class, true)) {
                    $data[$field] = $jsonObject->$jsonVar ? new \DateTime($jsonObject->$jsonVar) : null;
                } else if (is_a($type->type(), Type::class, true)) {
                    $typeClass = $type->type();
                    $data[$field] = $jsonObject->$jsonVar ? new $typeClass($jsonObject->$jsonVar) : null;
                } else if (is_subclass_of($type->type(), BaseResource::class) && $jsonObject->$jsonVar) {
                    $data[$field] = ($type->type())::fromJsonObject($jsonObject->$jsonVar);
                } else {
                    $data[$field] = $jsonObject->$jsonVar;
                }
            }
        }

        return new static($data);
    }

    /**
     * @throws \ReflectionException
     */
    public static function fieldsAndTypes(): array
    {
        $fields = [];
        foreach (Reflection::getResourceFields(static::class) as $field => $type) {
            $fields[Str::snake($field)] = $type;
        }
        return $fields;
    }

    public static function newQueryBuilder()
    {
        $className = (new \ReflectionClass(static::class))->getShortName();

        return new QueryBuilder(
            static::class,
            Str::lower(Str::plural($className))
        );
    }
}