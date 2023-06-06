<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Task;

use Gems\SnippetsActions\Export\ExportAction;
use Zalt\Html\UrlArrayAttribute;

/**
 * @package    Gems
 * @subpackage Task
 * @since      Class available since version 2.0
 */
class ExportRunnerBatch extends TaskRunnerBatch
{
    public function getDownloadUrl()
    {
        return UrlArrayAttribute::toUrlString($this->baseUrl + ['step' => ExportAction::STEP_DOWNLOAD]);
    }

    public function getJsAttributes(): array
    {
        $output = parent::getJsAttributes();

        $output[':restart-load'] = 'true';
        $output['init-url']     = UrlArrayAttribute::toUrlString($this->baseUrl + ['step' => ExportAction::STEP_BATCH, $this->progressParameterName => $this->progressParameterInitValue]);
        $output['run-url']      = UrlArrayAttribute::toUrlString($this->baseUrl + ['step' => ExportAction::STEP_BATCH, $this->progressParameterName => $this->progressParameterRunValue]);
        $output['restart-url']  = UrlArrayAttribute::toUrlString($this->baseUrl + ['step' => ExportAction::STEP_RESET]);
        $output['download-url'] = $this->getDownloadUrl();

        return $output;
    }
}