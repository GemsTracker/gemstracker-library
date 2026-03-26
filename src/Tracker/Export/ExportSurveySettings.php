<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Export;

use Gems\Export\Export;

/**
 * @package    Gems
 * @subpackage Tracker\Export
 * @since      Class available since version 1.0
 */
class ExportSurveySettings extends Export
{
    /**
     * This variable holds all registered export classes, may be changed in derived classes
     *
     * @var array Of classname => description
     */
    protected array $exportClasses =  [
        'TextExport' => 'Text',
    ];

    protected array $streamingExportClasses = [
    ];

    /**
     * Returns all registered export classes
     *
     * @return string[] Array Of classname => description
     */
    public function getExportClasses(bool $sensitiveData = true): array
    {
        return $this->exportClasses;
    }

    public function streamOnly(bool $sensitiveData = true): bool
    {
        return false;
    }
}