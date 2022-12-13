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

    public function validateGroupsConfig(): void
    {
        foreach ($this->groups as $keyCode => $group) {
            if ($group['ggp_code'] !== $keyCode) {
                throw new \InvalidArgumentException('Mismatch between group key "' . $keyCode . '" and code "' . $group['ggp_code'] . '"');
            }
        }
    }
}
