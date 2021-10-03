<?php

namespace App\SonarApi\Resources;

use App\SonarApi\Queries\QueryBuilder;

interface ResourceInterface
{
    /**
     * Defines which relations to always load in query.  Return associative array containing the relation
     * field name as key and value being anything non-null OR a closure that will receive a QueryBuilder
     * for the relation.
     */
    public function with(): array;

    /**
     * Return new instance of resource from the JSON response object.
     */
    public static function fromJsonObject(object $jsonObject): self;

    /**
     * Return instance of QueryBuilder for the resource.
     */
    public static function newQueryBuilder(): QueryBuilder;
}