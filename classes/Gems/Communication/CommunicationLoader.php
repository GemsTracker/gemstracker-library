<?php

namespace Gems\Communication;

use Gems\Communication\Http\HttpClient;
use Gems\Communication\Http\SmsClientInterface;
use Gems\Communication\JobMessenger\JobMessengerAbstract;
use Gems\Communication\JobMessenger\MailJobMessenger;
use Gems\Communication\JobMessenger\SmsJobMessenger;
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
     * @param $name
     * @return JobMessengerAbstract
     */
    public function getJobMessenger($name)
    {
        switch($name) {
            case MailJobMessenger::class:
            case 'MailJobMessenger':
            case 'mail':
                return $this->_loadClass('MailJobMessenger', true);

            case SmsJobMessenger::class:
            case 'SmsJobMessenger':
            case 'sms':
                return $this->_loadClass('SmsJobMessenger', true);
            default:
                return null;
        }
    }

    /**
     * @return SmsClientInterface
     * @throws \Gems_Exception|
     */
    public function getSmsClient($clientId='sms')
    {
        if (!$this->smsClient) {
            $config = $this->getConfig();
            if (isset($config[$clientId], $config[$clientId]['class'])) {
                $httpClient = $this->getHttpClient($config[$clientId]);
                if (class_exists($config[$clientId]['class'])) {
                    $class = $config[$clientId]['class'];
                    $smsClient = new $class($config[$clientId], $httpClient);
                } else {
                    $smsClient = $this->_getClass('smsClient', $config[$clientId]['class'], [$config[$clientId], $httpClient]);
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
