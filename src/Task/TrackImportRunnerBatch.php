<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Task;

use Zalt\Html\UrlArrayAttribute;

/**
 * @package    Gems
 * @subpackage Task
 * @since      Class available since version 1.0
 */
class TrackExportRunnerBatch extends TaskRunnerBatch
{
    public function getDownloadUrl()
    {
        return UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => 4]);
    }

    public function getJsAttributes(): array
    {
        $output = parent::getJsAttributes();

        $output[':restart-load'] = 'true';
        $output['init-url']     = UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => 3, $this->progressParameterName => $this->progressParameterInitValue]);
        $output['run-url']      = UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => 3, $this->progressParameterName => $this->progressParameterRunValue]);
        $output['restart-url']  = UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => 1]);
        $output['download-url'] = $this->getDownloadUrl();

        return $output;
    }
}