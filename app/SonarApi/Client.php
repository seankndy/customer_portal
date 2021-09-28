<?php

namespace App\SonarApi;

use App\SonarApi\Clients\Accounts;
use App\SonarApi\Clients\Tickets;
use App\SonarApi\Exceptions\SonarHttpException;
use App\SonarApi\Exceptions\SonarQueryException;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;

class Client
{
    private const API_URL = 'https://%s.sonar.software/api/graphql'; // %s replaced with instance name

    private GuzzleClientInterface $httpClient;

    private string $apiKey;

    private string $instanceName;

    public function __construct(
        GuzzleClientInterface $httpClient,
        string $apiKey,
        string $instanceName
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->instanceName = $instanceName;
    }

    public function accounts(): Accounts
    {
        return new Accounts($this);
    }

    public function tickets(): Tickets
    {
        return new Tickets($this);
    }

    /**
     * @param \GraphQL\Query|string $query
     * @return mixed
     * @throws SonarHttpException
     * @throws SonarQueryException
     */
    public function query($query, array $variables = [])
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf(self::API_URL, $this->instanceName),
                [
                    'http_errors' => true,
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'query' => (string)$query,
                        'variables' => $variables,
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
}
