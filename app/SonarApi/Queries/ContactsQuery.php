<?php

namespace App\SonarApi\Queries;

use App\SonarApi\Resources\Contact;

class ContactsQuery extends BaseQuery
{
    protected function resource(): string
    {
        return Contact::class;
    }

    protected function objectName(): string
    {
        return 'contacts';
    }
}