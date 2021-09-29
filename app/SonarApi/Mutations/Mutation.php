<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Queries\Query;

interface Mutation extends Query
{
    public function query(): \GraphQL\Mutation;
}