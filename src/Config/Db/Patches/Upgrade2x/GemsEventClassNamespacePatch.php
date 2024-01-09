<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsEventClassNamespacePatch extends PatchAbstract
{
    private array $eventColumns = [
        [ 'gems__rounds', 'gro_changed_event' ],
        [ 'gems__rounds', 'gro_display_event' ],
        [ 'gems__surveys', 'gsu_beforeanswering_event' ],
        [ 'gems__surveys', 'gsu_completed_event' ],
        [ 'gems__surveys', 'gsu_display_event' ],
        [ 'gems__tracks', 'gtr_beforefieldupdate_event' ],
        [ 'gems__tracks', 'gtr_calculation_event' ],
        [ 'gems__tracks', 'gtr_completed_event' ],
        [ 'gems__tracks', 'gtr_fieldupdate_event' ],
    ];

    public function getDescription(): string|null
    {
        return 'Update event class namespaces for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20240109000002;
    }

    public function up(): array
    {
        $statements = [];
        foreach ($this->eventColumns as $data) {
            list($table, $column) = $data;
            $statements[] = sprintf('UPDATE %s SET %s = REPLACE(%s, "Gems\\\\Event", "Gems\\\\Tracker\\\\TrackEvent") WHERE %s LIKE "Gems%%"', $table, $column, $column, $column);
        }

        return $statements;
    }
}
