<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config\Db\Patches\Upgrade2x
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;

/**
 * @package    Gems
 * @subpackage Config\Db\Patches\Upgrade2x
 * @since      Class available since version 1.0
 */
class GemsLogActivityNoForeignKeysPatch extends \Gems\Db\Migration\PatchAbstract
{
    private string $table = 'gems__log_activity';

    private array $foreignKeys = [
        [ 'gla_organization', 'gems__organizations', 'gor_id_organization' ],
    ];

    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Drop foreign keys from ' . $this->table;
    }

    /**
     * @inheritDoc
     */
    public function up(): array
    {
        $statements = [];
        foreach ($this->foreignKeys as $foreignKeyData) {
            list($col, $refTable, $refCol) = $foreignKeyData;
            if ($this->databaseInfo->tableHasForeignKey($this->table, $col, $refTable, $refCol)) {
                $foreignKey = $this->databaseInfo->getForeignKeyName($this->table, $col, $refTable, $refCol);
                $statements[] = sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $this->table, $foreignKey);
            }
        }

        return $statements;
    }
}