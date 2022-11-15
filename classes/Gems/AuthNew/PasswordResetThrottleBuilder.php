<?php

namespace Gems\AuthNew;

use Laminas\Db\Adapter\Adapter;

class PasswordResetThrottleBuilder
{
    public function __construct(
        private readonly array $config,
        private readonly Adapter $db,
    ) {
    }

    public function buildPasswordResetThrottle(string $ipAddress, int $organizationId): PasswordResetThrottle
    {
        return new PasswordResetThrottle(
            $this->config,
            $this->db,
            $ipAddress,
            $organizationId,
        );
    }
}
