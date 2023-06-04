<?php

namespace Gems\Communication\Http;

use Gems\Exception\ClientException;

class ApiKeyClient extends HttpClient
{
    protected function getApiKey()
    {
        if (isset($this->config, $this->config['api_key'])) {
            return $this->config['api_key'];
        }

        return null;
    }

    public function request($method, $uri = '', $options = [], $rawResponse = false)
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
