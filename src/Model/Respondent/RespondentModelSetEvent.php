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
class RespondentModelSetEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    public function __construct(
        protected readonly RespondentModel $model,
        protected readonly ?SnippetActionInterface $action = null,
    )
    { }

    public function getAction(): ? SnippetActionInterface
    {
        return $this->action;
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
}