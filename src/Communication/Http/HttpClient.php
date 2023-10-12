<?php

namespace Gems\Communication\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class HttpClient
{
    public static array $successCodes = [
        200,
        201,
        202,
        203,
        204,
        205
    ];

    protected Client $client;

    protected array|null $config = null;

    public function __construct(array $config, Client|null $client = null)
    {
        $this->config = $config;

        if ($client) {
            $this->client = $client;
        } else {
            $clientConfig = [];
            if ($this->config && isset($this->config['uri'])) {
                $clientConfig['base_uri'] = $this->config['uri'];
            }
            if ($this->config && isset($this->config['proxy'])) {
                $clientConfig['proxy'] = $this->config['proxy'];
            }

            $this->client = new Client($clientConfig);
        }
    }

    public function request(string $method, string $uri = '', array $options = [], bool $rawResponse = false): array|ResponseInterface
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch(ClientException $e) {
            throw new \Gems\Exception\ClientException($e->getMessage());
        }
        if ($response instanceof ResponseInterface) {
            if (in_array($response->getStatusCode(), self::$successCodes)) {
                if ($rawResponse) {
                    return $response;
                }
                $content = $response->getBody()->getContents();

                if (strpos($response->getHeaderLine('Content-Type'), 'application/json') !== false) {
                    return json_decode($content, true);
                }

                return $response;
            }
        }
        throw new \Gems\Exception\ClientException('No valid response from: ' . $uri);
    }
}
