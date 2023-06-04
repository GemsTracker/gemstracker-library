<?php

namespace Gems\Auth\Acl;

use Brick\VarExporter\VarExporter;

class GroupRepository
{
    public function __construct(
        public readonly GroupAdapterInterface $groupAdapter,
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

return [
    \'groups\' => [
        \'definition_date\' => \'' . date('Y-m-d H:i:s') . '\',
        \'groups\' => %s,
    ],
];' . PHP_EOL;

        $groups = VarExporter::export($this->groupAdapter->getGroupsConfig(), VarExporter::TRAILING_COMMA_IN_ARRAY, 2);

        return sprintf($template, $groups);
    }
}
