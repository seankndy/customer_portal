<?php

namespace App\SonarApi\Queries;

interface QueryInterface
{
    public function query(): \GraphQL\Query;

    public function variables(): array;
}