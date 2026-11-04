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
class TrackImportRunnerBatch extends TaskRunnerBatch
{
    protected int $step;

    public function getDownloadUrl()
    {
        return UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => $this->step]);
    }

    public function getJsAttributes(): array
    {
        $output = parent::getJsAttributes();

        $output[':restart-load'] = 'true';
        $output['init-url']     = UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => $this->step - 1, $this->progressParameterName => $this->progressParameterInitValue]);
        $output['run-url']      = UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => $this->step - 1, $this->progressParameterName => $this->progressParameterRunValue]);
        $output['restart-url']  = UrlArrayAttribute::toUrlString($this->baseUrl + ['current_step' => 1]);
        $output['download-url'] = $this->getDownloadUrl();

        return $output;
    }

    public function setFormStep(int $step): void
    {
        $this->step = $step;
    }
}