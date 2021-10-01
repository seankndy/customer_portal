<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Client;

class ClientMutator
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function run(BaseMutation $mutation)
    {
        $response = $this->client->mutate($mutation);

        if ($mutation->returnResource()) {
            return ($mutation->returnResource())::fromJsonObject($response->{$mutation->name()});
        }

        return $response;
    }
}