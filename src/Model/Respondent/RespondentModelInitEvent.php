<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Respondent;

use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsActions\SnippetActionInterface;

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @since      Class available since version 1.0
 */
class RespondentModelInitEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    public function __construct(
        protected readonly RespondentModel $model,
        protected array $labels,
    )
    { }

    public function addLabels(array $labels)
    {
        $this->labels = $labels + $this->labels;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @return MetaModelInterface
     */
    public function getMetaModel(): MetaModelInterface
    {
        return $this->model->getMetaModel();
    }

    /**
     * @return SurveyMaintenanceModel
     */
    public function getModel(): RespondentModel
    {
        return $this->model;
    }

    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }
}