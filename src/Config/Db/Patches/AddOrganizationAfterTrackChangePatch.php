<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config\Db\Patches
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config\Db\Patches;

use Gems\Config\ConfigAccessor;
use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

/**
 * @package    Gems
 * @subpackage Config\Db\Patches
 * @since      Class available since version 1.0
 */
class AddOrganizationAfterTrackChangePatch extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
        protected readonly ConfigAccessor $configAccessor
    )
    {  }

    public function getDescription(): string|null
    {
        return 'Add after track change route field to organizations';
    }

    public function getOrder(): int
    {
        return 20250221000000;
    }

    public function up(): array
    {
        $statements = [];
        if (!$this->databaseInfo->tableHasColumn('gems__organizations', 'gor_track_change_route')) {
            $statements[] = "ALTER TABLE gems__organizations ADD gor_track_change_route varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null AFTER gor_token_ask";
        }
        $statements[] = sprintf("UPDATE gems__organizations SET gor_track_change_route = '%s' WHERE gor_track_change_route IS NULL;", $this->configAccessor->getAfterTrackChangeDefaultRoute());
        return $statements;
    }
}
