<?php

namespace Gems\Communication\Http;

use Gems\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class ApiKeyClient extends HttpClient
{
    protected function getApiKey(): string|null
    {
        if (isset($this->config, $this->config['api_key'])) {
            return $this->config['api_key'];
        }

        return null;
    }

    public function request(string $method, string $uri = '', array $options = [], bool $rawResponse = false): ResponseInterface
    {
        $apiKey = $this->getApiKey();

        if ($apiKey === null) {
            throw new ClientException('No API key set for ' . static::class);
        }

        if (!isset($options['headers']) || !isset($options['headers']['Authorization'])) {
            $options['headers']['Authorization'] = 'Bearer ' . $apiKey;
        }

        return parent::request($method, $uri, $options, $rawResponse);
    }
}
