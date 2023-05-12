<?php

namespace Gems\AuthNew;

use Laminas\Db\Adapter\Adapter;

class LoginThrottleBuilder
{
    public function __construct(
        private readonly array $config,
        private readonly Adapter $db,
    ) {
    }

    public function buildLoginThrottle(string $loginName, int $organizationId): LoginThrottle
    {
        return new LoginThrottle(
            $this->config,
            $this->db,
            $loginName,
            $organizationId,
        );
    }
}
