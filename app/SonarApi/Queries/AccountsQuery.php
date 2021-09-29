<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Resources\Account;

class AccountsQuery extends BaseQuery
{
    protected function resource(): string
    {
        return Account::class;
    }

    protected function objectName(): string
    {
        return 'accounts';
    }
}