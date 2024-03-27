<?php

namespace Gems\Messenger;

use Psr\Container\ContainerInterface;

class FailureSenderContainer implements ContainerInterface
{
    protected readonly array $config;
    public function __construct(
        array $config
    )
    {
        $this->config = $config['messenger'] ?? [];
    }

    public function get(string $id): ?string
    {
        return $this->config['transports'][$id]['failure_transport'] ?? $this->config['failure_transport'] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->config['transports'][$id]['failure_transport']) || isset($this->config['failure_transport']);
    }
}