<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 * Generic controller class for showing and editing template variables
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class TemplateAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    protected $createEditSnippets = 'ModelTabFormSnippetGeneric';

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \Gems\Model\TemplateModel('templates', $this->escort->project);

        return $model;
    }

    public function getEditTitle()
    {
        $data = $this->getModel()->loadFirst();

        //Add location to the subject
        $subject = $data['name'];

        return sprintf($this->_('Edit %s %s'), $this->getTopic(1), $subject);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Used templates');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('template', 'templates', $count);
    }

    /**
     * Reset action
     *
     * Deletes the template-local.ini (by means of a model function) and displays
     * success or fail messages and returns to the index
     */
    public function resetAction()
    {
        $model = $this->getModel();

        $id = $this->getInstanceId();
        if ($model->reset($id)) {
            $this->addMessage(sprintf($this->_('Resetting values for template %s to defaults successful'), $id), 'success');
        } else {
            $this->addMessage(sprintf($this->_('Resetting values for template %s to defaults failed'), $id), 'warning');
        }

        $this->_reroute(array('action'=>'edit', 'id'=>$id), true);
    }
}
