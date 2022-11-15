<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

use Gems\Condition\ConditionLoader;
use Gems\Event\Application\ModelCreateEvent;
use MUtil\Model;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gcon_name' => SORT_ASC],
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = ['conditions'];

    /**
     * @var \Gems\Condition\ConditionInterface
     */
    protected $condition;

    /**
     * @var ConditionLoader
     */
    public $conditionLoader;

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deleteParameters = [
        'conditionId' => '_getIdParam'
    ];
    
    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'ConditionDeleteSnippet';

    /**
     * @var EventDispatcherInterface
     */
    public $event;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Condition\\ConditionSearchFormSnippet'];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showParameters = [
        'condition' => 'getCondition',
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\Rounds\\ConditionRoundsTableSnippet',
        'ConditionAndOrTableSnippet',
    ];

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
    protected function createModel($detailed, $action)
    {
        $model = $this->conditionLoader->getConditionModel();

        if ($detailed) {
            if (('edit' == $action) || ('create' == $action)) {
                $model->applyEditSettings(('create' == $action));
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
        }

        $event = new ModelCreateEvent($model, $detailed, $action);
        $this->event->dispatch($event, $event->name);

        return $model;
    }

    /**
     * @return \Gems\Condition\ConditionInterface
     * @throws \Gems\Exception\Coding
     */
    public function getCondition()
    {
        $id = $this->request->getAttribute(Model::REQUEST_ID);

        if ($id && (!$this->condition)) {
            $this->condition = $this->conditionLoader->loadCondition($id);
        }

        return $this->condition;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Conditions');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('condition', 'conditions', $count);
    }
    
    public function createAction()
    {
        parent::createAction();
    }
    
    public function editAction()
    {
        parent::editAction();
    }
    
    /**
     * Action for showing an item page with title
     */
    public function showAction()
    {
        $model = $this->getModel();
        if ($model->hasMeta('ConditionShowSnippets')) {
            $this->showSnippets = array_merge($this->showSnippets, (array) $model->getMeta('ConditionShowSnippets'));
        }

        parent::showAction();
    }
}
