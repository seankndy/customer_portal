<?php

namespace App\SonarApi\Resources;

class TicketRecipient extends BaseResource
{
    public int $id;

    public string $name;

    public string $emailAddress;
}