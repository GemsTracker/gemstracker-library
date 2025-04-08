<?php

declare(strict_types=1);

namespace Gems\Handlers\Respondent;

use Gems\Batch\BatchRunnerLoader;
use Gems\Exception;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\Transform\FixedValueTransformer;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\Export\RespondentExportSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\Tracker\AvailableTracksSnippet;
use Gems\Snippets\Tracker\Buttons\TrackActionButtonRow;
use Gems\Snippets\Tracker\Buttons\TrackIndexButtonRow;
use Gems\Snippets\Tracker\DeleteTrackSnippet;
use Gems\Snippets\Tracker\EditTrackSnippet;
use Gems\Snippets\Tracker\TrackTableSnippet;
use Gems\Snippets\Tracker\TrackTokenOverviewSnippet;
use Gems\Snippets\Tracker\TrackUsageOverviewSnippet;
use Gems\Snippets\Tracker\TrackUsageTextDetailsSnippet;
use Gems\Tracker;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Tracker\Model\RespondentTrackModel;
use Gems\Tracker\RespondentTrack;
use Gems\User\Mask\MaskRepository;
use Mezzio\Session\SessionMiddleware;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class RespondentTrackHandler extends RespondentChildHandlerAbstract
{

    protected array $autofilterParameters = [
        'bridgeMode'      => BridgeInterface::MODE_ROWS,
        'extraFilter'     => 'getRespondentFilter',
        'extraSort'       => ['gr2t_start_date' => SORT_DESC],
        'menuEditRoutes' => ['edit'],
        'menuShowRoutes' => ['show track' => 'show'],
        'respondent'      => 'getRespondent',
    ];

    protected array $autofilterSnippets = [
        TrackTableSnippet::class,
        TrackIndexButtonRow::class,
        AvailableTracksSnippet::class,
    ];

    protected array $createEditParameters = [
        'createData'        => false,
        'formTitle'         => 'getTrackTitle',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    ];

    protected array $createEditSnippets = [
        EditTrackSnippet::class,
        TrackUsageTextDetailsSnippet::class,
        TrackTokenOverviewSnippet::class,
        TrackUsageOverviewSnippet::class,
    ];

    protected array $deleteParameters = [
        'addCurrentParent'   => true,
        'addCurrentSiblings' => true,
        'requestUndelete'    => false,
        'respondentTrack'    => 'getRespondentTrack',
        'respondentTrackId'  => 'getRespondentTrackId',
        'trackEngine'        => 'getTrackEngine',
        'trackId'            => 'getTrackId',
    ];

    protected array $exportParameters = [
        'formTitle'         => 'getTrackTitle',
        'respondentTrack'   => 'getRespondentTrack',
    ];

    protected array $exportSnippets = [
        RespondentExportSnippet::class,
    ];

    protected array $deleteSnippets = [
        DeleteTrackSnippet::class,
        TrackTokenOverviewSnippet::class,
        TrackUsageOverviewSnippet::class,
    ];

    protected array $showParameters = [
        'contentTitle'      => 'getTrackTitle',
        'extraFilter'       => 'getNoRespondentFilter',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'displayMenu'       => false,
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    ];

    protected array $showSnippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        TrackActionButtonRow::class,
        TrackUsageTextDetailsSnippet::class,
        TrackTokenOverviewSnippet::class,
        TrackUsageOverviewSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        RespondentRepository $respondentRepository,
        CurrentUserRepository $currentUserRepository,
        protected Tracker $tracker,
        protected MaskRepository $maskRepository,
        protected BatchRunnerLoader $batchRunnerLoader,
    ) {
        parent::__construct($responder, $translate, $cache, $respondentRepository, $currentUserRepository);
    }

    public function checkTrackAction(): ?ResponseInterface
    {
        $respondent  = $this->getRespondent();
        $respTrackId = $this->getRespondentTrackId();
        $trackEngine = $this->getTrackEngine();
        $batch = $this->tracker->checkTrackRounds(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'trackCheckRoundsFor_' . $respTrackId,
            $this->currentUser->getUserId(),
            ['gr2t_id_respondent_track' => $respTrackId]
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle(sprintf(
            $this->_("Checking round assignments for track '%s' of respondent %s, %s."),
            $trackEngine->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        ));

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
     * Check the tokens for a single track
     */
    public function checkTrackAnswersAction(): ?ResponseInterface
    {
        $respondent  = $this->getRespondent();
        $respTrackId = $this->getRespondentTrackId();
        $trackEngine = $this->getTrackEngine();
        $batch = $this->tracker->recalculateTokens(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'answersCheckAllFor__' . $respTrackId,
            $this->currentUser->getUserId(),
            ['gto_id_respondent_track' => $respTrackId]
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle(sprintf(
            $this->_("Checking the surveys in track '%s' of respondent %s, %s for answers."),
            $trackEngine->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        ));

        $batchRunner->setJobInfo([
            $this->_('This task checks all tokens for this track for this respondent for answers.'),
        ]);

        return $batchRunner->getResponse($this->request);
    }

    protected function createModel(bool $detailed, string $action): RespondentTrackModel
    {
        $model = $this->tracker->getRespondentTrackModel();
        if ($detailed) {
            $engine = $this->getTrackEngine();

            switch ($action) {
                case 'export':
                case 'show':
                    $model->applyDetailSettings($engine);
                    break;

                case 'edit':
                    $model->applyEditSettings($engine);
                    $metaModel = $model->getMetaModel();
                    $metaModel->addTransformer(new FixedValueTransformer([
                        'gr2t_id_user' => $this->getRespondentId(),
                        'gr2t_id_organization' => $this->request->getAttribute(MetaModelInterface::REQUEST_ID2),
                        'gr2t_id_respondent_track' => $this->request->getAttribute(Model::RESPONDENT_TRACK),
                    ]));
                    break;

                case 'create':
                    $model->applyEditSettings($engine);
                    $metaModel = $model->getMetaModel();
                    $metaModel->addTransformer(new FixedValueTransformer([
                        'gr2t_id_user' => $this->getRespondentId(),
                        'gr2t_id_organization' => $this->request->getAttribute(MetaModelInterface::REQUEST_ID2),
                        'gr2t_id_track' => $this->request->getAttribute(Model::TRACK_ID),
                    ]));
                    break;

                default:
                    $model->applyEditSettings($engine);
                    break;
            }

            $apply = false;
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Export a single track
     */
    public function exportAction()
    {
        if ($this->exportSnippets) {
            $params = $this->_processParameters($this->exportParameters);

            $this->addSnippets($this->exportSnippets, $params);
        }
    }

    /**
     * Get a blocking filter when no respondent is passed on
     *
     * @return array
     */
    public function getNoRespondentFilter(): array
    {
        if (!$this->getRespondentId()) {
            return ['1 = 0'];
        }
        return [];
    }

    public function getRespondentFilter()
    {
        $respondent = $this->getRespondent();
        return [
            'gr2t_id_user'         => $respondent->getId(),
            'gr2t_id_organization' => $respondent->getOrganizationId(),
        ];
    }

    /**
     * Retrieve the respondent track
     * (So we don't need to repeat that for every snippet.)
     *
     * @return RespondentTrack
     */
    public function getRespondentTrack(): RespondentTrack
    {
        static $respTrack;

        if ($respTrack instanceof RespondentTrack) {
            return $respTrack;
        }

        $respTrackId = $this->request->getAttribute(\Gems\Model::RESPONDENT_TRACK);

        if ($respTrackId) {
            $respTrack = $this->tracker->getRespondentTrack((int) $respTrackId);
        } else {
            throw new Exception($this->_('No track specified for respondent!'));
        }
        if (! $respTrack instanceof RespondentTrack) {
            throw new Exception($this->_('No track found for respondent!'));
        }

        // Otherwise return the last created track (yeah some implementations are not correct!)
        return $respTrack;
    }

    public function getRespondentTrackId(): ?int
    {
        return (int)$this->request->getAttribute(\Gems\Model::RESPONDENT_TRACK);
    }

    public function getTopic(int $count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     * Retrieve the track engine
     *
     * @return TrackEngineInterface
     */
    public function getTrackEngine(): TrackEngineInterface
    {
        static $engine;

        if ($engine instanceof TrackEngineInterface) {
            return $engine;
        }

        try {
            $respTrack = $this->getRespondentTrack();
            if ($respTrack instanceof RespondentTrack) {
                $engine = $respTrack->getTrackEngine();
            }

        } catch (\Exception $ex) {
        }

        if (! $engine instanceof TrackEngineInterface) {
            $trackId = $this->request->getAttribute(\Gems\Model::TRACK_ID);

            if (! $trackId) {
                throw new Exception($this->_('No track engine specified!'));
            }

            $engine = $this->tracker->getTrackEngine($trackId);
        }

        return $engine;
    }

    /**
     * Retrieve the track ID
     *
     * @return int|null
     */
    public function getTrackId(): ?int
    {
        $trackEngine = $this->getTrackEngine();
        if ($trackEngine) {
            return $trackEngine->getTrackId();
        }
        return null;
    }
    protected function getTrackTitle(): ?string
    {
        $respTrack   = $this->getRespondentTrack();
        if ($respTrack) {
            $trackEngine = $respTrack->getTrackEngine();

            if ($this->maskRepository->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                // Set params
                return sprintf(
                    $this->_('%s track for respondent nr %s'),
                    $trackEngine->getTrackName(),
                    $respTrack->getPatientNumber(),
                );
            }

            // Set params
            return sprintf(
                $this->_('%s track for respondent nr %s: %s'),
                $trackEngine->getTrackName(),
                $respTrack->getPatientNumber(),
                $respTrack->getRespondentName(),
            );
        }

        return null;
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function recalcFieldsAction(): ?ResponseInterface
    {
        $respondent  = $this->getRespondent();
        $respTrackId = $this->getRespondentTrackId();
        $trackEngine = $this->getTrackEngine();

        $batch = $this->tracker->recalcTrackFields(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'trackRecalcFieldsFor_' . $respTrackId,
            ['gr2t_id_respondent_track' => $respTrackId]
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle(sprintf(
            $this->_("Recalculating fields for track '%s' of respondent %s, %s."),
            $trackEngine->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        ));

        $batchRunner->setJobInfo([
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
     * Undelete a track
     */
    public function undeleteAction()
    {
        $this->deleteParameters['requestUndelete'] = true;

        parent::deleteAction();
    }
}
