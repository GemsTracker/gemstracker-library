<?php

namespace Gems\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FailureSenderContainer implements ContainerInterface
{
    protected readonly array $config;
    public function __construct(
        private readonly ContainerInterface $container,
        array $config
    )
    {
        $this->config = $config['messenger'] ?? [];
    }

    public function get(string $id): ?TransportInterface
    {
        $failureTransportName = $this->config['transports'][$id]['failure_transport'] ?? $this->config['failure_transport'] ?? null;
        if (!$failureTransportName) {
            return null;
        }

        if (!$this->container->has($failureTransportName)) {
            return null;
        }

        return $this->container->get($failureTransportName);
    }

    public function has(string $id): bool
    {
        return isset($this->config['transports'][$id]['failure_transport']) || isset($this->config['failure_transport']);
    }
}