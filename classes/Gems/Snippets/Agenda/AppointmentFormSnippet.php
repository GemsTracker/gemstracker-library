<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentFormSnippet extends \Gems\Snippets\ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var GemsLoader
     */
    protected $loader;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        parent::afterSave($changed);

        $model = $this->getModel();
        if ($model instanceof \Gems\Model\AppointmentModel) {
            $count = $model->getChangedTokenCount();
            if ($count) {
                $this->addMessage(sprintf($this->plural('%d token changed', '%d tokens changed', $count), $count));
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof \Gems\Model\AppointmentModel) {
            $this->model = $this->loader->getModels()->createAppointmentModel();
            $this->model->applyDetailSettings();
        }
        $this->model->set('gap_admission_time', 'formatFunction', array($this, 'displayDate'));
        $this->model->set('gap_discharge_time', 'formatFunction', array($this, 'displayDate'));

        return $this->model;
    }
}
