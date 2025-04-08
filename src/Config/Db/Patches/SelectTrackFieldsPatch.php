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
class SelectTrackFieldsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Fill gems__track_fields gtf_field_value_keys column for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20250110000000;
    }

    public function up(): array
    {
        return ["UPDATE gems__track_fields SET gtf_field_value_keys = gtf_field_values WHERE (gtf_field_value_keys IS NULL OR gtf_field_value_keys = '') AND gtf_field_values IS NOT NULL"];
    }
}