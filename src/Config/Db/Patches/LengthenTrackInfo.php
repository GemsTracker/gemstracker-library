<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config\Db\Patches
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\PatchAbstract;

/**
 * @package    Gems
 * @subpackage Config\Db\Patches
 * @since      Class available since version 1.0
 */
class LengthenTrackInfo extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Lengthen gems__respondent2track gr2t_track_info field to 500 characters';
    }

    public function down(): ?array
    {
        return ["ALTER TABLE gems__respondent2track CHANGE gr2t_track_info gr2t_track_info varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"];
    }

    public function getOrder(): int
    {
        return 20210209000000;
    }

    public function up(): array
    {
        return ["ALTER TABLE gems__respondent2track CHANGE gr2t_track_info gr2t_track_info varchar(500) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"];
    }

}