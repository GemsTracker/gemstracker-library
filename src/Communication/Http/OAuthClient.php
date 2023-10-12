<?php

namespace Gems\Communication\Http;

use Gems\Exception\ClientException;
use Gems\Repository\OauthClientRepository;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class OAuthClient extends HttpClient
{
    public function __construct(
        public readonly string $name,
        array $config,
        protected readonly OauthClientRepository $oauthClientRepository,
        Client|null $client = null,
    )
    {
        parent::__construct($config, $client);
    }

    /**
     * Get a saved or new Access Token
     *
     * @return string|null
     * @throws ClientException
     */
    protected function getAccessToken(): string|null
    {
        if ($this->oauthClientRepository->hasAccessToken($this->name)) {
            if ($this->oauthClientRepository->accessTokenIsValid($this->name)) {
                return $this->oauthClientRepository->getAccessToken($this->name);
            }
            if ($this->oauthClientRepository->refreshTokenIsValid($this->name)) {
                return $this->getNewAccessTokenFromRefreshToken($this->oauthClientRepository->getRefreshToken($this->name));
            }
        }
        return $this->getNewAccessToken();
    }

    /**
     * Do a request for a new Access Token
     *
     * @return string|null
     * @throws ClientException
     */
    protected function getNewAccessToken(): string|null
    {
        if ($this->config && isset($this->config['credentials'], $this->config['credentials']['grant_type'])) {
            if ($this->config['credentials']['grant_type'] === 'password') {

                $options = [
                    'form_params' => $this->config['credentials'],
                ];
                return $this->getAccessTokenFromOptions($options);
            }
        }
        throw new ClientException('Could not retrieve access token from ' . $this->name);
    }

    /**
     * Do a request for a new Access Token
     */
    protected function getAccessTokenFromOptions(array $options): string|null
    {
        $accessTokenUri = 'access_token';
        if (isset($this->config, $this->config['access_token_uri'])) {
            $accessTokenUri = $this->config['access_token_uri'];
        }

        $response = parent::request('POST', $accessTokenUri, $options);

        if (is_array($response) && isset($response['access_token'], $response['expires_in'])) {
            $refreshToken = null;
            if (isset($response['refresh_token'])) {
                $refreshToken = $response['refresh_token'];
            }

            $refreshInterval = null;
            if (isset($this->config['refresh_token_interval'])) {
                $refreshInterval = $this->config['refresh_token_interval'];
            }

            $this->oauthClientRepository->setAccessToken($response['access_token'], $response['expires_in'], $refreshToken, $refreshInterval);

            return $response['access_token'];
        }

        return null;
    }

    /**
     * Get a new Access Token from a refresh token
     */
    protected function getNewAccessTokenFromRefreshToken(string $refreshToken): string|null
    {
        if ($this->config && isset($this->config['credentials'], $this->config['credentials']['grant_type'])) {
            if ($this->config['credentials']['grant_type'] === 'password') {

                $options = [
                    'form_params' => $this->config['credentials'],
                ];

                $options['form_params']['grant_type'] = 'refresh_token';
                $options['form_params']['refresh_token'] = $refreshToken;

                unset($options['form_params']['username']);
                unset($options['form_params']['password']);

                return $this->getAccessTokenFromOptions($options);
            }
        }
        throw new ClientException('Could not retrieve access token from ' . $this->name);
    }

    /**
     * Do a request to the current configged client with an access token as Bearer Token
     */
    public function request(string $method, string $uri = '', array $options = [], bool $rawResponse = false): ResponseInterface
    {
        $accessToken = $this->getAccessToken();

        if (!isset($options['headers']) || !isset($options['headers']['Authorization'])) {
            $options['headers']['Authorization']['Bearer'] = $accessToken;
        }

        return parent::request($method, $uri, $options, $rawResponse);
    }
}