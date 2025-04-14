<?php

declare(strict_types=1);

namespace Gems\Communication\Http;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class HttpClientFactory implements FactoryInterface
{


    public function __construct(
        private readonly string $groupName = 'http-clients',
    )
    {}

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /**
         * @var array $config
         */
        $config = $container->get('config');
        if (!isset($config[$this->groupName])) {
            throw new ServiceNotCreatedException(sprintf('Could not create service %s. Config group %s not found', $requestedName, $this->groupName));
        }
        if (!isset($config[$this->groupName], $config[$this->groupName][$requestedName])) {
            throw new ServiceNotCreatedException(sprintf('Could not create service %s. Config with name %s not found', $requestedName, $requestedName));
        }

        return new $requestedName($config[$this->groupName][$requestedName]);
    }
}