<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class Gems_Default_ConditionAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraSort'   => array('gcon_name' => SORT_ASC),
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('conditions');

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
    protected $deleteParameters = array(
        'conditionId' => '_getIdParam'
    );
    
    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'ConditionDeleteSnippet';

    /**
     * @var \Gems\Event\EventDispatcher
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
     * /
    protected $showParameters = array(
        'bridgeMode'      => \MUtil_Model_Bridge_BridgeAbstract::MODE_SINGLE_ROW,
        );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\Rounds\\ConditionRoundsTableSnippet',
        'ConditionAndOrTableSnippet',
        );

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getConditionModel();

        if ($detailed) {
            if (('edit' == $action) || ('create' == $action)) {
                $model->applyEditSettings(('create' == $action));
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
        }

        $event = new \Gems\Event\Application\ModelCreateEvent($model, $detailed, $action);
        $this->event->dispatch($event, $event->name);

        return $model;
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
        $this->addScript();
    }
    
    public function editAction()
    {
        parent::editAction();
        $this->addScript();
    }
    
    /**
     * This script disables the onchange that is fired just before the click on the submit button fires.
     * The onchange submits the form, changes the csrf token and then the submit button fires with the old
     * csrf that is invalid. This could prevent a needed (fake)submit to change values, but for now it
     * does not seem like a problem.
     */
    protected function addScript() 
    {
        $view = $this->view;
        \MUtil_JQuery::enableView($view);

        $jquery = $view->jQuery();
        $jquery->enable();  //Just to make sure

        $handler = \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();

        $script = "$('input[type=\"submit\"]').mousedown(function(e) {
     e.preventDefault(); // prevents blur() to be called when clicking submit
});";
        $fields = array(
            'jQuery'  => $handler,
        );

        $js = str_replace(array_keys($fields), $fields, $script);

        $jquery->addOnLoad($js);
    }

}
