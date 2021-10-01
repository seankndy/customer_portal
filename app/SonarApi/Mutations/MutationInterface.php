<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Queries\QueryInterface;

interface MutationInterface extends QueryInterface
{
    public function query(): \GraphQL\Mutation;
}