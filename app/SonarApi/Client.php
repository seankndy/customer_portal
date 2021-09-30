<?php

namespace App\SonarApi;

use App\SonarApi\Mutations\BaseMutation;
use App\SonarApi\Mutations\Mutation;
use App\SonarApi\Queries\AccountsQuery;
use App\SonarApi\Queries\ContactsQuery;
use App\SonarApi\Queries\Query;
use App\SonarApi\Queries\TicketsQuery;
use App\SonarApi\Exceptions\SonarHttpException;
use App\SonarApi\Exceptions\SonarQueryException;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;

class Client
{
    private GuzzleClientInterface $httpClient;

    private string $apiKey;

    private string $url;

    public function __construct(
        GuzzleClientInterface $httpClient,
        string $apiKey,
        string $url
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->url = $url;
    }

    public function accounts(): AccountsQuery
    {
        return new AccountsQuery($this);
    }

    public function tickets(): TicketsQuery
    {
        return new TicketsQuery($this);
    }

    public function contacts(): ContactsQuery
    {
        return new ContactsQuery($this);
    }

    public function mutations()
    {
        return new class($this) {
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
        };
    }

    /**
     * @return mixed
     * @throws SonarHttpException
     * @throws SonarQueryException
     */
    public function query(Query $query)
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('%s/api/graphql', $this->url),
                [
                    'http_errors' => true,
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'query' => (string)$query->query(),
                        'variables' => $query->variables(),
                    ]
                ]
            );

            $jsonObject = \json_decode($response->getBody()->getContents(), false);
        } catch (\Exception $e) {
            throw new SonarHttpException("Failed to POST to Sonar's GraphQL API: " . $e->getMessage());
        }

        if (isset($jsonObject->errors) && $jsonObject->errors) {
            throw new SonarQueryException("Query returned errors.", $jsonObject->errors);
        }

        return $jsonObject->data;
    }

    /**
     * @throws SonarHttpException
     * @throws SonarQueryException
     */
    public function mutate(Mutation $mutation)
    {
        return $this->query($mutation);
    }
}
