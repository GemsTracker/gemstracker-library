<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class TrackMaintenanceAction extends \Gems\Actions\TrackMaintenanceWithEngineActionAbstract
{
    /**
     *
     * @var \Gems\AccessLog
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
    protected $autofilterParameters = [
        'extraSort'   => ['gtr_track_name' => SORT_ASC],
        'trackEngine' => null,
        'trackId'     => null,
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = ['track', 'tracks'];

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
    protected $createParameters = [
        'trackEngine' => null,
    ];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;
    
    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = ['active' => 1];
    
    protected $deleteParameters = [
        'trackId' => '_getIdParam'
    ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Track\\TrackDeleteSnippet';

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
    protected $exportParameters = [];

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
    protected $importParameters = [
        'trackEngine' => null,
        'trackId'     => null,
    ];

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
    protected $indexStartSnippets = [
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackMaintenance\\TrackMaintenanceSearchSnippet'
    ];

    protected $showParameters = [
        'trackId' => '_getIdParam'
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
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
    ];

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
    public $summarizedActions = ['index', 'autofilter', 'check-all', 'recalc-all-fields'];

    /**
     * The request ID value
     *
     * @return ?string The request ID value
     */
    protected function _getIdParam(): ?string
    {
        return $this->request->getAttribute('trackId');
    }

    /**
     * Action for making a copy of a track
     */
    public function copyAction()
    {
        $trackId    = $this->_getIdParam();
        $engine     = $this->getTrackEngine();
        $newTrackId = $engine->copyTrack($trackId);

        $this->_reroute(urlOptions: array('action' => 'edit', \MUtil\Model::REQUEST_ID => $newTrackId));
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
     * @return \Gems\Model_TrackModel
     */
    public function createModel($detailed, $action)
    {
        $model = $this->tracker->getTrackModel();
        $model->applyFormatting($detailed);
        $model->addFilter(array("gtr_track_class != 'SingleSurveyEngine'"));

        return $model;
    }

    /**
     * Edit a single item
     */
    public function editAction()
    {
        $this->createEditSnippets = $this->tracker->getTrackEngineEditSnippets();

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
        
        if (array_key_exists('active', $filter)) {
            switch ($filter['active']) {
                case 0:
                    // Inactive
                    $filter['gtr_active'] = 0;
                    break;
                    
                case 1:
                    // Active now
                    $filter['gtr_active'] = 1;
                    $filter[] = 'gtr_date_start <= CURRENT_TIMESTAMP AND (gtr_date_until IS NULL OR gtr_date_until >= CURRENT_TIMESTAMP)';
                    break;
                    
                case 2:
                    //Expired
                    $filter['gtr_active'] = 1;
                    $filter[] = 'gtr_date_until < CURRENT_TIMESTAMP';
                    break;
                case 3:
                    // Future
                    $filter['gtr_active'] = 1;
                    $filter[] = 'gtr_date_start > CURRENT_TIMESTAMP';
                    break;
            }
            unset($filter['active']);
        }

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
