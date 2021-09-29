<?php

namespace App\SonarApi\Queries;

interface Query
{
    public function query(): \GraphQL\Query;

    public function variables(): array;
}