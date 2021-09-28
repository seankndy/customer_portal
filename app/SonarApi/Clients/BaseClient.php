<?php

namespace App\SonarApi\Clients;

use App\SonarApi\Client;

abstract class BaseClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}