<?php

namespace Gems\Auth\Acl;

use Brick\VarExporter\VarExporter;

class GroupRepository
{
    public function __construct(
        private readonly GroupAdapterInterface $groupAdapter,
    ) {
    }

    public function getDependingGroups(string $groupCode): array
    {
        $dependents = [];
        foreach ($this->groupAdapter->getGroupsConfig() as $group) {
            if (in_array($groupCode, $group['ggp_may_set_groups']) || $groupCode === $group['ggp_default_group']) {
                $dependents[] = $group['ggp_code'];
            }
        }

        return $dependents;
    }

    public function buildGroupConfigFile(): string
    {
        $template = '<?php

namespace Gems\Config;

class Group
{
    public function __invoke(): array
    {
        return [
            \'definition_date\' => \'' . date('Y-m-d H:i:s') . '\',
            \'groups\' => %s,
        ];
    }
}' . PHP_EOL;

        $groups = VarExporter::export($this->groupAdapter->getGroupsConfig(), VarExporter::TRAILING_COMMA_IN_ARRAY);
        $groups = str_replace("\n", "\n            ", $groups);

        return sprintf($template, $groups);
    }
}
