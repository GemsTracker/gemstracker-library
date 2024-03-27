<?php

namespace Gems\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

class RetryStrategyContainer implements ContainerInterface
{
    protected readonly array $config;
    public function __construct(
        array $config
    )
    {
        $this->config = $config['messenger'] ?? [];
    }

    public function get(string $id): ?RetryStrategyInterface
    {
        if ($this->has($id)) {
            $transportRetryOptions = $this->config['transport'][$id]['retry_strategy'] ?? [];
            $maxRetries = $transportRetryOptions['max_retries'] ?? 3;
            $delayMilliseconds = $transportRetryOptions['delay'] ?? 1000;
            $multiplier = $transportRetryOptions['multiplier'] ?? 1;
            $maxDelayMilliseconds = $transportRetryOptions['max_delay'] ?? 0;
            return new MultiplierRetryStrategy(
                $maxRetries,
                $delayMilliseconds,
                $multiplier,
                $maxDelayMilliseconds,
            );
        }

        return null;
    }

    public function has(string $id): bool
    {
        return isset($this->config['transport'][$id]);
    }
}