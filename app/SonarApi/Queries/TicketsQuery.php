<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Resources\Ticket;

class TicketsQuery extends BaseQuery
{
    protected function resource(): string
    {
        return Ticket::class;
    }

    protected function objectName(): string
    {
        return 'tickets';
    }
}