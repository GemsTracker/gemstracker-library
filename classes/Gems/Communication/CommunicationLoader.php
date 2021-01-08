<?php

namespace Gems\Communication;

use Gems\Communication\Http\HttpClient;
use Gems\Communication\Http\SmsClientInterface;
use GuzzleHttp\Client;

class CommunicationLoader extends \Gems_Loader_TargetLoaderAbstract
{
    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Communication';

    /**
     * @var SmsClientInterface
     */
    protected $smsClient;

    protected function getConfig()
    {
        $configFile = APPLICATION_PATH . '/configs/communication.php';
        if (file_exists($configFile)) {
            $configArray = require($configFile);
            return $configArray;
        }
        return [];
    }

    public function getHttpClient($config=null)
    {
        $clientConfig = [];
        if ($config && isset($config['uri'])) {
            $clientConfig['base_uri'] = $config['uri'];
        }
        if ($config && isset($config['proxy'])) {
            $clientConfig['proxy'] = $config['proxy'];
        }

        return new Client($clientConfig);
    }

    /**
     * @return SmsClientInterface
     * @throws \Gems_Exception|
     */
    public function getSmsClient()
    {
        if (!$this->smsClient) {
            $config = $this->getConfig();
            if (isset($config['sms'], $config['sms']['class'])) {
                $httpClient = $this->getHttpClient($config['sms']);
                if (class_exists($config['sms']['class'])) {
                    $class = $config['sms']['class'];
                    $smsClient = new $class($config['sms'], $httpClient);
                } else {
                    $smsClient = $this->_getClass('smsClient', $config['sms']['class'], [$config['sms'], $httpClient]);
                }
                if (!($smsClient instanceof SmsClientInterface)) {
                    throw new \Gems_Exception('Sms client could not be loaded from config');
                }
                $this->smsClient = $smsClient;
            }
        }
        return $this->smsClient;
    }
}
