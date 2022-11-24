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

use Gems\Db\ResultFetcher;
use Gems\MenuNew\RouteHelper;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker;
use Gems\Tracker\Model\TrackModel;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class TrackRoundsHandler extends TrackMaintenanceWithEngineHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Tracker\\Rounds\\RoundsTableSnippet'];

    /**
     *
     * @var \Gems\Util\BasePath
     */
    public $basepath;

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['track', 'tracks'];

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
    protected array $createEditParameters = [
        'roundId'     => 'getRoundId',
    ];

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
    protected array $deleteParameters = [
        'roundId'     => 'getRoundId',
    ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = ['Tracker\\Rounds\\RoundDeleteSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Tracker\\Rounds\\RoundsTitleSnippet', 'AutosearchWithIdSnippet'];

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
    protected array $showParameters = [
        'roundId'     => 'getRoundId',
        'surveyId'    => 'getSurveyId',
    ];

    public function __construct(
        RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        Tracker $tracker,
        protected ResultFetcher $resultFetcher,
        protected TrackDataRepository $trackDataRepository,
    ) {
        parent::__construct($routeHelper, $responder, $translate, $tracker);
    }

    public function autofilterAction(bool $resetMvc = true): void
    {
        parent::autofilterAction($resetMvc);

        //If allowed, add the sort action
        /*if ($this->menu->findAllowedController($this->getRequest()->getControllerName(), 'sort')) {
            $buttons = $this->_helper->SortableTable('sort', 'rid');
            // First element is the wrapper
            $this->html[0]->append($buttons);
        }*/
    }

    /**
     * Create a new round
     */
    public function createAction(): void
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

        /*\MUtil\JQuery::enableView($this->view);
        $this->view->headScript()->appendFile($this->basepath->getBasePath()  .  '/gems/js/jquery.showOnChecked.js');

        $this->view->headScript()->appendScript("jQuery(document).ready(function($) {
            $('input[name=\"organizations[]\"]').closest('div').showOnChecked( { showInput: $('#org_specific_round-1') });
        });");*/
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
        $trackEngine = $this->getTrackEngine();
        $trackId     = $trackEngine->getTrackId();

        $model = $trackEngine->getRoundModel($detailed, $action);
        $model->set('gro_id_track', 'default', $trackId);
        $model->applyParameters(['gro_id_track' => $trackId]);

        if ($detailed) {
            if ($action == 'create') {
                // Set the default round order
                $newOrder = $this->resultFetcher->fetchOne(
                    "SELECT MAX(gro_id_order) FROM gems__rounds WHERE gro_id_track = ?",
                    [$trackId]
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
    public function editAction(): void
    {
        $this->createEditPrepare();
        parent::editAction();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Rounds') . ' TrackRoundsHandler.php' .
            $this->trackDataRepository->getTrackTitle((int)$this->_getIdParam());
    }

    /**
     * Get the current round id
     *
     * @return int
     */
    protected function getRoundId(): ?int
    {
        $id = $this->request->getAttribute(\Gems\Model::ROUND_ID);
        if ($id) {
            return (int)$id;
        }
        return null;
    }

    /**
     * Get the current survey id using the round id
     *
     * @return ?int
     */
    protected function getSurveyId(): ?int
    {
        $roundId = $this->request->getAttribute(Model::REQUEST_ID);
        if ($roundId) {

            $surveyId = $this->resultFetcher->fetchOne('SELECT gro_id_survey FROM gems__rounds WHERE gro_id_round = ?', $roundId);
            if ($surveyId !== null || $surveyId !== false) {
                return (int)$surveyId;
            }
        }
        return null;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('round', 'rounds', $count);
    }

    /**
     * Action for showing an item page
     */
    public function showAction(): void
    {
        $this->showSnippets = $this->getTrackEngine()->getRoundShowSnippetNames();

        parent::showAction();
    }

    public function sortAction()
    {
        //$this->_helper->getHelper('SortableTable')->ajaxAction('gems__rounds','gro_id_round', 'gro_id_order');
    }
}