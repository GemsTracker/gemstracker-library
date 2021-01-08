<?php

namespace Gems\Communication\Http;

use Gems\Exception\ClientException;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class SpryngSmsClient extends ApiKeyClient implements SmsClientInterface
{
    /**
     * @var string|null Default originator
     */
    protected $defaultOriginator;

    public function __construct($config, Client $client = null)
    {
        parent::__construct($config, $client);
        if (isset($config['default_originator'])) {
            $this->defaultOriginator = $config['default_originator'];
        }

    }

    protected function handleResponse($response)
    {
        if ($response instanceof ResponseInterface) {
            switch($response->getStatusCode()) {
                case 200:
                    return true;
                case 500:
                case 503:
                    $content = $response->getBody()->getContents();

                    if (strpos($response->getHeaderLine('Content-Type'), 'application/json') !== false) {
                        $content = json_decode($content, true);
                        if (isset($content['message'])) {
                            throw new ClientException('Server error: ' . $content['message']);
                        }
                    }

                    throw new ClientException('server error');
                default:
                    $content = $response->getBody()->getContents();

                    if (strpos($response->getHeaderLine('Content-Type'), 'application/json') !== false) {
                        $content = json_decode($content, true);
                        if (isset($content['message'])) {
                            throw new ClientException('Request error: ' . $content['message']);
                        }
                    }

                    throw new ClientException('Request error');
            }
        }

        throw new ClientException('Incorrect message response');
    }

    public function sendMessage($number, $body, $originator=null)
    {
        if ($originator === null) {
            if (!$this->defaultOriginator) {
                throw new ClientException('No sms originator set');
            }
            $originator = $this->defaultOriginator;
        }

        $reference = sprintf('%s: %s', GEMS_PROJECT_NAME, APPLICATION_ENV);

        $message = [
            'encoding' => 'auto',
            'body' => $body,
            'originator' => $originator,
            'route' => 'business',
            'reference' => $reference,
            'recipients' => [
                $number,
            ]
        ];

        if (isset($this->config, $this->config['route'])) {
            $message['route'] = $this->config['route'];
        }
        if (isset($this->config, $this->config['reference'])) {
            $message['reference'] = $this->config['reference'];
        }

        $options = [
            'json' => $message,
        ];

        $response = $this->request('POST', '', $options, true);

        return $this->handleResponse($response);

    }
}
