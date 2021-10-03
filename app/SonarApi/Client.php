<?php

namespace App\SonarApi;

use App\SonarApi\Mutations\ClientMutator;
use App\SonarApi\Mutations\MutationInterface;
use App\SonarApi\Queries\QueryInterface;
use App\SonarApi\Queries\QueryBuilder;
use App\SonarApi\Exceptions\SonarHttpException;
use App\SonarApi\Exceptions\SonarQueryException;
use App\SonarApi\Resources\Account;
use App\SonarApi\Resources\Contact;
use App\SonarApi\Resources\Ticket;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;

class Client
{
    private GuzzleClientInterface $httpClient;

    private string $apiKey;

    private string $url;

    private array $rootQueryBuilders = [];

    public function __construct(
        GuzzleClientInterface $httpClient,
        string $apiKey,
        string $url,
        array $rootQueryBuilders = []
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->url = $url;
        $this->rootQueryBuilders = \array_merge([
            'accounts' => Account::class,
            'tickets' => Ticket::class,
            'contacts' => Contact::class,
        ], $this->rootQueryBuilders);
    }

    public function __call(string $name, array $args)
    {
        if (isset($this->rootQueryBuilders[$name])) {
            return ($this->rootQueryBuilders[$name])::newQueryBuilder()->setClient($this);
        }

        throw new \Error('Call to undefined method '.self::class.'::'.$name.'()');
    }

    public function mutations(): ClientMutator
    {
        return new ClientMutator($this);
    }

    /**
     * @return mixed
     * @throws SonarHttpException
     * @throws SonarQueryException
     */
    public function query(QueryInterface $query)
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
    public function mutate(MutationInterface $mutation)
    {
        return $this->query($mutation);
    }
}
