<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Export;

use Gems\Snippets\Export\ExportBatchSnippet;
use Gems\Snippets\Tracker\Export\ExportSurveySettingsDownloadStepSnippet;
use Gems\Snippets\Tracker\Export\ExportSurveySettingsSnippet;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @since      Class available since version 1.0
 */
class ExportSurveySettingsAction extends ExportAction
{
    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ExportSurveySettingsSnippet::class,
        ExportBatchSnippet::class,
        ExportSurveySettingsDownloadStepSnippet::class,
    ];

    public function isDetailed() : bool
    {
        return true;
    }
}