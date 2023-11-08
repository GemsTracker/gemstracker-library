<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Event\Application
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Event\Application;

use Gems\Tracker\Model\SurveyMaintenanceModel;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Gems
 * @subpackage Event\Application
 * @since      Class available since version 1.0
 */
class SurveyModelSetEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * ModelCreateEvent constructor.
     *
     * @param SurveyMaintenanceModel $model
     * @param bool                   $editable
     * @param bool                   $detailed
     */
    public function __construct(
        protected readonly SurveyMaintenanceModel $model,
        protected readonly bool $detailed,
        protected readonly bool $editable,
    )
    { }

    /**
     * @return bool
     */
    public function getEditable()
    {
        return $this->editable;
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
    public function getModel(): SurveyMaintenanceModel
    {
        return $this->model;
    }

    /**
     * @return bool
     */
    public function isDetailed()
    {
        return $this->detailed;
    }
}