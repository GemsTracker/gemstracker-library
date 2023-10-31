<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Usage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Usage;

use Zalt\Snippets\DeleteModeEnum;

/**
 * @package    Gems
 * @subpackage Usage
 * @since      Class available since version 1.0
 */
interface UsageCounterInterface
{
    public function getFieldName(): string;

    public function getUsageMode(): DeleteModeEnum;

    public function getUsageReport(): array;

    public function hasUsage($value): bool;

    public function setUsageMode(DeleteModeEnum $value): void;

    public function setFieldName(string $fieldName): void;

    public function setUsageReport(mixed $value): array;
}