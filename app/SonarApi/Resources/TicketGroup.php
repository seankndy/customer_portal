<?php

namespace App\SonarApi\Resources;

class TicketGroup extends BaseResource
{
    public int $id;

    public string $name;

    public bool $enabled;
}