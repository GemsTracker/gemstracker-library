<?php

namespace Gems\Log;

use Gems\Factory\MonologFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Loggers
{
    public function __construct(
        protected ContainerInterface $container,
        protected array $config
    )
    {}

    public function getLogger(string $loggerName): LoggerInterface
    {
        $factory = new MonologFactory();
        $logger = $factory($this->container, $loggerName);

        if (!$logger instanceof LoggerInterface) {
            throw new RuntimeException(sprintf('Logger %s could not be created', $loggerName));
        }

        return $logger;
    }

    public function listLoggers(): array
    {
        if (isset($this->config['log'])) {
            return array_keys($this->config['log']);
        }
        return [];
    }
}