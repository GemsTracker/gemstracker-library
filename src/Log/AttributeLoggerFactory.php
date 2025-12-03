<?php

namespace Gems\Log;

use Gems\Log\Attribute\AsStreamLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ReflectionClass;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AttributeLoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $reflection = new ReflectionClass($requestedName);

        $loggerName = $this->getLoggerName($reflection);

        $logger = new Logger($loggerName);

        $this->processAttributes($reflection, $logger);

        return new $requestedName($logger);
    }

    private function getLoggerName(ReflectionClass $reflection): string
    {
        foreach($reflection->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if (isset($instance->name)) {
                return $instance->name;
            }
        }

        return $reflection->getShortName();
    }

    private function processAttributes(ReflectionClass $reflection, Logger $logger): void
    {
        foreach($reflection->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();

            match(true) {
                $instance instanceof AsStreamLogger => $logger->pushHandler($this->createStreamHandler($instance)),
                default => null,
            };
        }

        if (empty($logger->getHandlers())) {
            throw new \RuntimeException('No handlers configured for logger {$reflection->getName()}');
        }
    }

    private function createStreamHandler(AsStreamLogger $config): StreamHandler
    {
        return new StreamHandler($config->path, $config->level);
    }
}