<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Batch\BatchRunnerLoader;
use Gems\Menu\RouteHelper;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Tracker;
use Gems\Tracker\Model\TrackModel;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionMiddleware;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class TrackMaintenanceHandler extends TrackMaintenanceWithEngineHandlerAbstract
{
    /**
     *
     * @var \Gems\Audit\AuditLog
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
    protected array $autofilterParameters = [
        'extraSort'   => ['gtr_track_name' => SORT_ASC],
        'trackEngine' => null,
        'trackId'     => null,
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['track', 'tracks'];

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
    protected array $createParameters = [
        'trackEngine' => null,
    ];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;
    
    /**
     * The default search data to use.
     */
    protected array $defaultSearchData = ['active' => 1];
    
    protected array $deleteParameters = [
        'trackId' => '_getIdParam'
    ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = ['Track\\TrackDeleteSnippet'];

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
    protected array $exportParameters = [];

    /**
     * The snippets used for the export action
     *
     * @var mixed String or array of snippets name
     */
    protected array $exportSnippets = ['Tracker\\Export\\ExportTrackSnippetGeneric'];

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
    protected array $importParameters = [
        'trackEngine' => null,
        'trackId'     => null,
    ];

    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected array $importSnippets = ['Tracker\\Import\\ImportTrackSnippetGeneric'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackMaintenance\\TrackMaintenanceSearchSnippet'
    ];

    protected array $showParameters = [
        'trackId' => '_getIdParam'
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelDetailTableSnippet',
        CurrentButtonRowSnippet::class,
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
    public array $summarizedActions = [
        'index',
        'autofilter',
        'check-all',
        'recalc-all-fields'
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        Tracker $tracker,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected RouteHelper $routeHelper,
    ) {
        parent::__construct($responder, $translate, $cache, $tracker);
    }

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


        return new RedirectResponse($this->routeHelper->getRouteUrl('track-builder.track-maintenance.edit', [
            \MUtil\Model::REQUEST_ID => $newTrackId,
        ]));
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function checkAllAction()
    {
        $batch = $this->tracker->checkTrackRounds(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'allTrackCheckRounds',
            $this->currentUserId,
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($this->_('Checking round assignments for all tracks.'));

        $batchRunner->setJobInfo([
            $this->_('Updates existing token description and order to the current round description and order.'),
            $this->_('Updates the survey of unanswered tokens when the round survey was changed.'),
            $this->_('Removes unanswered tokens when the round is no longer active.'),
            $this->_('Creates new tokens for new rounds.'),
            $this->_('Checks the validity dates and times of unanswered tokens, using the current round settings.'),
            $this->_('Run this code when a track has changed or when the code has changed and the track must be adjusted.'),
            $this->_('If you do not run this code after changing a track, then the old tracks remain as they were and only newly created tracks will reflect the changes.'),
        ]);

        return $batchRunner->getResponse($this->request);
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function checkTrackAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->getTrackEngine();
        $batch = $this->tracker->checkTrackRounds(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'trackCheckRounds' . $id,
            $this->currentUserId,
            ['gr2t_id_track' => $id]
            );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = sprintf($this->_("Checking round assignments for track %d '%s'."), $id, $track->getTrackName());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);

        return $batchRunner->getResponse($this->request);
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction(): void
    {
        $this->createEditSnippets = $this->tracker->getTrackEngineEditSnippets();

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
     * @return TrackModel
     */
    public function createModel(bool $detailed, string $action): TrackModel
    {
        $model = $this->tracker->getTrackModel();
        $model->applyFormatting($detailed);
        $model->addFilter(array("gtr_track_class != 'SingleSurveyEngine'"));

        return $model;
    }

    /**
     * Edit a single item
     */
    public function editAction(): void
    {
        $this->createEditSnippets = $this->tracker->getTrackEngineEditSnippets();

        parent::editAction();
    }

    /**
     * Generic model based export action
     */
    public function exportAction(): void
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
    public function getSearchFilter(bool $useRequest = true): array
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
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function recalcAllFieldsAction()
    {
        $batch = $this->tracker->recalcTrackFields(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'trackRecalcAllFields'
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($this->_('Recalculating fields for all tracks.'));
        $batchRunner->setJobInfo([
            $this->_('Track field recalculation'),
            $this->_('Recalculates the values the fields should have.'),
            $this->_('Couple existing appointments to tracks where an appointment field is not filled.'),
            $this->_('Overwrite existing appointments to tracks e.g. when the filters have changed.'),
            $this->_('Checks the validity dates and times of unanswered tokens, using the current round settings.'),
            $this->_('Run this code when automatically calculated track fields have changed, when the appointment filters used by this track have changed or when the code has changed and the track must be adjusted.'),
            $this->_('If you do not run this code after changing track fields, then the old fields values remain as they were and only newly changed and newly created tracks will reflect the changes.'),
        ]);

        return $batchRunner->getResponse($this->request);
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function recalcFieldsAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->getTrackEngine();

        $batch = $this->tracker->recalcTrackFields(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'trackRecalcAllFields',
            ['gr2t_id_track' => $id]
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = sprintf($this->_("Recalculating fields for track %d '%s'."), $id, $track->getTrackName());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_('Track field recalculation'),
            $this->_('Recalculates the values the fields should have.'),
            $this->_('Couple existing appointments to tracks where an appointment field is not filled.'),
            $this->_('Overwrite existing appointments to tracks e.g. when the filters have changed.'),
            $this->_('Checks the validity dates and times of unanswered tokens, using the current round settings.'),
            $this->_('Run this code when automatically calculated track fields have changed, when the appointment filters used by this track have changed or when the code has changed and the track must be adjusted.'),
            $this->_('If you do not run this code after changing track fields, then the old fields values remain as they were and only newly changed and newly created tracks will reflect the changes.'),
        ]);

        return $batchRunner->getResponse($this->request);
    }

    /**
     *  Pass the h3 tag to all snippets except the first one
     */    
    public function showAction(): void
    {
        $showSnippets = $this->showSnippets;
        $first = array_shift($showSnippets);
        $next  = $showSnippets;
        
        $this->showSnippets = [$first];
        parent::showAction();
        
        $this->showParameters['tagName'] = 'h3';
        
        $this->showSnippets = $next;
        parent::showAction();

        array_unshift($this->showSnippets, $first);
    }
}
