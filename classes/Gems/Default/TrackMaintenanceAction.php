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
 * @since      Class available since version 1.0
 */
class Gems_Default_TrackMaintenanceAction extends \Gems_Default_TrackMaintenanceWithEngineActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'extraSort'   => array('gtr_track_name' => SORT_ASC),
        'trackEngine' => null,
        'trackId'     => null,
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('track', 'tracks');

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = array(
        'trackEngine' => null,
    );

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The parameters used for the export action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $exportParameters = array();

    /**
     * The snippets used for the export action
     *
     * @var mixed String or array of snippets name
     */
    protected $exportSnippets = 'Tracker\\Export\\ExportTrackSnippetGeneric';

    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $importParameters = array(
        'trackEngine' => null,
        'trackId'     => null,
    );

    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected $importSnippets = 'Tracker\\Import\\ImportTrackSnippetGeneric';

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackMaintenance\\TrackMaintenanceSearchSnippet'
        );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\TrackVisualDefinitionTitleSnippet',
        'Tracker\\TrackVisualDefinitionSnippet',
        'Tracker\\Fields\\FieldsTitleSnippet',
        'Tracker\\Fields\\FieldsTableSnippet',
        'Tracker\\Buttons\\NewFieldButtonRow',
        'Tracker\\Rounds\\RoundsTitleSnippet',
        'Tracker\\Rounds\\RoundsTableSnippet',
        'Tracker\\Buttons\\NewRoundButtonRow',
        );

    /**
     * Array of the actions that use a summarized version of the model.
     *
     * This determines the value of $detailed in createAction(). As it is usually
     * less of a problem to use a $detailed model with an action that should use
     * a summarized model and I guess there will usually be more detailed actions
     * than summarized ones it seems less work to specify these.
     *
     * @var array $summarizedActions Array of the actions that use a
     * summarized version of the model.
     */
    public $summarizedActions = array('index', 'autofilter', 'check-all', 'recalc-all-fields');

    /**
     * Action for making a copy of a track
     */
    public function copyAction()
    {
        $trackId    = $this->_getIdParam();
        $engine     = $this->getTrackEngine();
        $newTrackId = $engine->copyTrack($trackId);

        $this->_reroute(array('action' => 'edit', \MUtil_Model::REQUEST_ID => $newTrackId));
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->checkTrackRounds('trackCheckRoundsAll', $this->currentUser->getUserId());
        $this->_helper->BatchRunner($batch, $this->_('Checking round assignments for all tracks.'), $this->accesslog);

        $this->addSnippet('Track\\CheckRoundsInformation');
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function checkTrackAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->getTrackEngine();
        $where = $this->db->quoteInto('gr2t_id_track = ?', $id);
        $batch = $this->loader->getTracker()->checkTrackRounds(
                'trackCheckRounds' . $id,
                $this->currentUser->getUserId(),
                $where
                );

        $title = sprintf($this->_("Checking round assignments for track %d '%s'."), $id, $track->getTrackName());
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Track\\CheckRoundsInformation');
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        $this->createEditSnippets = $this->loader->getTracker()->getTrackEngineEditSnippets();

        parent::createAction();
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
        $tracker = $this->loader->getTracker();

        $model = $tracker->getTrackModel();
        $model->applyFormatting($detailed);
        $model->addFilter(array("gtr_track_class != 'SingleSurveyEngine'"));

        return $model;
    }

    /**
     * Edit a single item
     */
    public function editAction()
    {
        $this->createEditSnippets = $this->loader->getTracker()->getTrackEngineEditSnippets();

        parent::editAction();
    }

    /**
     * Generic model based export action
     */
    public function exportAction()
    {
        if ($this->exportSnippets) {
            $params = $this->_processParameters($this->exportParameters);

            $this->addSnippets($this->exportSnippets, $params);
        }
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        if (isset($filter['org']) && strlen($filter['org'])) {
            $filter[] = 'gtr_organizations LIKE "%|' . $filter['org'] . '|%"';
            unset($filter['org']);
        }

        return $filter;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function recalcAllFieldsAction()
    {
        $batch = $this->loader->getTracker()->recalcTrackFields(
                'trackRecalcAllFields'
                );
        $this->_helper->BatchRunner($batch, $this->_('Recalculating fields for all tracks.'), $this->accesslog);

        $this->addSnippet('Track\\RecalcFieldsInformation');
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function recalcFieldsAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->getTrackEngine();
        $where = $this->db->quoteInto('gr2t_id_track = ?', $id);
        $batch = $this->loader->getTracker()->recalcTrackFields(
                'trackRecalcFields' . $id,
                $where
                );

        $title = sprintf($this->_("Recalculating fields for track %d '%s'."), $id, $track->getTrackName(), $this->accesslog);
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Track\\RecalcFieldsInformation');
    }

    /**
     *  Pass the h3 tag to all snippets except the first one
     */    
    public function showAction()
    {
        $showSnippets = $this->showSnippets;
        $first = array_shift($showSnippets);
        $next  = $showSnippets;
        
        $this->showSnippets = $first;
        parent::showAction();
        
        $this->showParameters['tagName'] = 'h3';
        
        $this->showSnippets = $next;
        parent::showAction();
        
        $this->showSnippets = array_unshift($next, $first);
    }
}
