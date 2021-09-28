<?php

namespace App\SonarApi\Resources;

class TicketCategory extends BaseResource
{
    public int $id;

    public string $name;

    public bool $enabled;
}