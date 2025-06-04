<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config\Db\Patches\Upgrade2x
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

/**
 * @package    Gems
 * @subpackage Config\Db\Patches\Upgrade2x
 * @since      Class available since version 1.0
 */
class TrackEngineUpgrade extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Upgrade NextStepEngine to AnyStepEngine ';
    }

    public function getOrder(): int
    {
        return 20240604000001;
    }

    public function up(): array
    {
        $statements = ["UPDATE gems__tracks SET gtr_track_class = 'AnyStepEngine' WHERE gtr_track_class != 'AnyStepEngine'"];

        return $statements;
    }
}