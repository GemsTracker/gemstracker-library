<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class Gems_Default_TrackRoundsAction extends \Gems_Default_TrackMaintenanceWithEngineActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker\\Rounds\\RoundsTableSnippet';

    /**
     *
     * @var \Gems_Util_BasePath
     */
    public $basepath;

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('track', 'tracks');

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array(
        'roundId'     => 'getRoundId',
    );

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

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
        'roundId'     => 'getRoundId',
        );

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Tracker\\Rounds\\RoundDeleteSnippet';

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Tracker\\Rounds\\RoundsTitleSnippet', 'AutosearchWithIdSnippet');

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array(
        'roundId'     => 'getRoundId',
        'surveyId'    => 'getSurveyId',
    );
    
    public function autofilterAction($resetMvc = true)
    {
        parent::autofilterAction($resetMvc);
        
        $buttons = $this->_helper->SortableTable('sort', 'rid');

        // First element is the wrapper
        $this->html[0]->append($buttons);
    }

    /**
     * Create a new round
     */
    public function createAction()
    {
        $this->createEditPrepare();
        parent::createAction();
    }

    /**
     * Preparations for creating and editing
     */
    protected function createEditPrepare()
    {
        $this->createEditSnippets = $this->getTrackEngine()->getRoundEditSnippetNames();

        \MUtil_JQuery::enableView($this->view);
        $this->view->headScript()->appendFile($this->basepath->getBasePath()  .  '/gems/js/jquery.showOnChecked.js');

        if (\MUtil_Bootstrap::enabled()) {
            $this->view->headScript()->appendScript("jQuery(document).ready(function($) {
                $('input[name=\"organizations[]\"]').closest('div').showOnChecked( { showInput: $('#org_specific_round-1') });
            });");
        } else {
            $this->view->headScript()->appendScript("jQuery(document).ready(function($) {
                $('input[name=\"organizations[]\"]').closest('tr').showOnChecked( { showInput: $('#org_specific_round-1') });
            });");
        }
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \Gems_Model_TrackModel
     */
    public function createModel($detailed, $action)
    {
        $trackEngine = $this->getTrackEngine();
        $trackId     = $trackEngine->getTrackId();

        $model = $trackEngine->getRoundModel($detailed, $action);
        $model->set('gro_id_track', 'default', $trackId);

        if ($detailed) {
            if ($action == 'create') {
                // Set the default round order
                $newOrder = $this->db->fetchOne(
                        "SELECT MAX(gro_id_order) FROM gems__rounds WHERE gro_id_track = ?",
                        $trackId
                        );

                if ($newOrder) {
                    $model->set('gro_id_order', 'default', $newOrder + 10);
                } else {
                    $model->set('gro_valid_after_source', 'default', 'rtr');
                }
            }
        }

        return $model;
    }

    /**
     * Action for showing a edit item page
     */
    public function editAction()
    {
        $this->createEditPrepare();
        parent::editAction();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Rounds') . ' ' .
            $this->util->getTrackData()->getTrackTitle($this->_getIdParam());
    }

    /**
     * Get the current round id
     *
     * @return int
     */
    protected function getRoundId()
    {
        return $this->_getParam(\Gems_Model::ROUND_ID);
    }

    /**
     * Get the current survey id using the round id
     *
     * @return int
     */
    protected function getSurveyId()
    {
        return $this->db->fetchOne(
                "SELECT gro_id_survey FROM gems__rounds WHERE gro_id_round = ?",
                $this->_getParam(\Gems_Model::ROUND_ID)
                );
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('round', 'rounds', $count);
    }

    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        $this->showSnippets = $this->getTrackEngine()->getRoundShowSnippetNames();

        return parent::showAction();
    }
    
    public function sortAction()
    {
        $this->_helper->getHelper('SortableTable')->ajaxAction('gems__rounds','gro_id_round', 'gro_id_order');        
    }
}