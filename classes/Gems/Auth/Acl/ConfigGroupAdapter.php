<?php

namespace Gems\Auth\Acl;

class ConfigGroupAdapter implements GroupAdapterInterface
{
    private readonly array $groups;

    public function __construct(array $config)
    {
        $this->groups = $config['groups']['groups'] ?? [];
    }

    public function getGroupsConfig(): array
    {
        return $this->groups;
    }
}
