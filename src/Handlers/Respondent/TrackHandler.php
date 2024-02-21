<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Batch\BatchRunnerLoader;
use Gems\Legacy\CurrentUserRepository;
use Gems\Pdf;
use Gems\Project\ProjectSettings;
use Gems\Repository\RespondentRepository;
use Gems\Tracker;
use Gems\Tracker\Model\RespondentTrackModel;
use Gems\User\Mask\MaskRepository;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Controller for editing respondent tracks, including their tokens
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class TrackHandler extends RespondentChildHandlerAbstract
{
    /**
     * The parameters used for the create actions, overrules any values in
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
        'createData'  => true,
        'formTitle'   => 'getCreateTrackTitle',
        'trackEngine' => 'getTrackEngine',
        'csrfName'    => 'getCsrfTokenName',
        'csrfToken'   => 'getCsrfToken',
        'session'     => 'getSession',
    ];

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected array $createSnippets = [
        'Tracker\\TrackUsageOverviewSnippet',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\EditTrackSnippet',
        'Tracker\\TrackSurveyOverviewSnippet',
    ];

    /**
     * The parameters used for the insert action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $insertParameters = [
        'csrfName'    => 'getCsrfTokenName',
        'csrfToken'   => 'getCsrfToken',
        'createData' => true,
        'formTitle'  => 'getInsertInTrackTitle',
        'model'      => null,
    ];

    /**
     * Snippets used for inserting a survey
     *
     * @var mixed String or array of snippets name
     */
    protected array $insertSnippets = ['Tracker\\InsertSurveySnippet'];

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
    protected array $viewParameters = [
        'contentTitle' => 'getViewTrackTitle',
        'trackEngine'  => 'getTrackEngine',
        'trackId'      => 'getTrackId',
    ];

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected array $viewSnippets = [
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
        'Tracker\\Buttons\\TrackActionButtonRow',
        'Tracker\\TrackSurveyOverviewSnippet',
    ];

    /**
     * The parameters used for the viewSurveys action.
     */
    protected array $viewSurveyParameters = [
        'surveyId' => 'getSurveyId',
    ];

    /**
     * Snippets used for showing survey questions
     *
     * @var mixed String or array of snippets name
     */
    protected array $viewSurveySnippets = [
        'Survey\\SurveyQuestionsSnippet'
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        RespondentRepository $respondentRepository,
        CurrentUserRepository $currentUserRepository,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected MaskRepository $maskRepository,
        protected ProjectSettings $projectSettings,
        protected Pdf $pdf,
        protected Tracker $tracker,
    ) {
        parent::__construct($responder, $translate, $cache, $respondentRepository, $currentUserRepository);
    }

    /**
     * Action for showing a create new item page
     *
     * Uses separate createSnippets instead of createEditSnipppets
     */
    public function createAction(): void
    {
        if ($this->createSnippets) {
            $params = $this->_processParameters($this->createParameters + $this->createEditParameters);

            $this->addSnippets($this->createSnippets, $params);
        }
    }

    protected function createModel(bool $detailed, string $action): RespondentTrackModel
    {
        $apply = true;
        $model = $this->tracker->getRespondentTrackModel();
        if ($detailed) {
            $engine = $this->getTrackEngine();

            if ($engine) {
                switch ($action) {
                    case 'export-track':
                    case 'show-track':
                        $model->applyDetailSettings($engine);
                        break;

                    default:
                        $model->applyEditSettings($engine);
                        break;
                }

                $apply = false;
            }
        }
        if ($apply) {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Get the title for creating a track
     *
     * @return string
     */
    protected function getCreateTrackTitle()
    {
        $respondent = $this->getRespondent();

        return sprintf(
            $this->_('Adding the %s track to respondent %s: %s'),
            $this->getTrackEngine()->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
    }

    /**
     * Get the title for creating a track
     *
     * @return string
     */
    protected function getInsertInTrackTitle()
    {
        $respondent = $this->getRespondent();

        return sprintf(
            $this->_('Inserting a survey in a track for respondent %s: %s'),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
    }

    /**
     * Retrieve the survey ID
     *
     * @return int|null
     */
    public function getSurveyId()
    {
        return $this->request->getAttribute(\Gems\Model::SURVEY_ID);
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
     * Retrieve the track engine
     *
     * @return \Gems\Tracker\Engine\TrackEngineInterface
     */
    public function getTrackEngine()
    {
        static $engine;

        if ($engine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            return $engine;
        }

        if (! $engine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            $trackId = $this->request->getAttribute(\Gems\Model::TRACK_ID);

            if (! $trackId) {
                throw new \Gems\Exception($this->_('No track engine specified!'));
            }

            $engine = $this->tracker->getTrackEngine($trackId);
        }

        return $engine;
    }

    /**
     * Retrieve the track ID
     *
     * @return int
     */
    public function getTrackId()
    {
        $trackEngine = $this->getTrackEngine();
        if ($trackEngine) {
            return $trackEngine->getTrackId();
        }
    }

    /**
     * Get the title for viewing track usage
     *
     * @return string
     */
    protected function getViewTrackTitle()
    {
        $trackEngine = $this->getTrackEngine();

        $respondent = $this->getRespondent();

        // Set params
        return sprintf(
            $this->_('%s track assignments for respondent nr %s: %s'),
            $trackEngine->getTrackName(),
            $this->request->getAttribute(\MUtil\Model::REQUEST_ID1),
            $this->getRespondent()->getFullName()
        );
    }

    /**
     * Insert a single survey into a track
     * @return void
     */
    public function insertAction()
    {
        if ($this->insertSnippets) {
            $params = $this->_processParameters($this->insertParameters);

            $this->addSnippets($this->insertSnippets, $params);
        }
    }

    /**
     * Show information on a single track type assigned to a respondent
     */
    public function viewAction()
    {
        if ($this->viewSnippets) {
            $params = $this->_processParameters($this->viewParameters);

            $this->addSnippets($this->viewSnippets, $params);
        }
    }

    /**
     * Used in AddTracksSnippet to show a preview for an insertable survey
     */
    public function viewSurveyAction()
    {
        $params = $this->_processParameters($this->viewSurveyParameters);
        $this->addSnippets($this->viewSurveySnippets, $params);
    }
}
