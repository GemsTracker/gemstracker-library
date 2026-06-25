<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Export\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Export\Type;

use Gems\Batch\BatchRunner;
use Gems\Task\ExportRunnerBatch;

/**
 * @package    Gems
 * @subpackage Export\Type
 * @since      Class available since version 1.0
 */
interface ApplyExportBatchTypeInterface
{
    public function applyExportBatch(ExportRunnerBatch $batch): void;
}