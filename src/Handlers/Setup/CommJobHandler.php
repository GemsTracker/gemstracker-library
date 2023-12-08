<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Batch\BatchRunnerLoader;
use Gems\Db\ResultFetcher;
use Gems\Handlers\BrowseChangeUsageHandler;
use Gems\Handlers\Setup\CommunicationActions\CommJobBrowseSearchAction;
use Gems\Handlers\Setup\CommunicationActions\CommJobMonitorAction;
use Gems\Handlers\Setup\CommunicationActions\CommJobShowAction;
use Gems\Handlers\Setup\CommunicationActions\CommLockAction;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Model\Setup\CommJobModel;
use Gems\Repository\CommJobRepository;
use Gems\Snippets\Communication\CommJobButtonRowSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker;
use Mezzio\Session\SessionMiddleware;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\ConstructorModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class CommJobHandler extends BrowseChangeUsageHandler
{
    use ConstructorModelHandlerTrait;

    /**
     * @inheritdoc
     */
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => CommJobBrowseSearchAction::class,
        'create'     => CreateAction::class,
        'export'     => ExportAction::class,
        'edit'       => EditAction::class,
        'delete'     => DeleteAction::class,
        'show'       => CommJobShowAction::class,
        'lock'       => CommLockAction::class,
        'monitor'    => CommJobMonitorAction::class,
    ];

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['comm-jobs',];

    /**
     * @var array
     */
    public array $config;

    protected int $currentUserId;

    /**
     * @var null|LoggerInterface
     */
    public $cronLog = null;

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CommJobButtonRowSnippet::class
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        CurrentUserRepository $currentUserRepository,
        protected ResultFetcher $resultFetcher,
        protected ProjectOverloader $overloader,
        protected Tracker $tracker,
        protected readonly BatchRunnerLoader $batchRunnerLoader,
        protected readonly CommJobModel $commJobModel,
        protected readonly CommJobRepository $commJobRepository,
        protected readonly RouteHelper $routeHelper,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);

        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->model = $this->commJobModel;
    }

    /**
     * Execute a single mail job
     */
    public function executeAction($preview = false)
    {
        $jobId = intval($this->request->getAttribute(MetaModelInterface::REQUEST_ID));
        $session = $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $batch = new TaskRunnerBatch('commjob-execute-' . $jobId, $this->overloader, $session);
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        if ($this->cronLog instanceof LoggerInterface) {
            $batch->setMessageLogger($this->cronLog);
        }

        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (!$batch->isLoaded() && !is_null(($jobId))) {
            $batch->addMessage(sprintf(
                    $this->_('Starting single message job %s'),
                    $jobId
                    ));

            // Check for unprocessed tokens
            $this->tracker->loadCompletedTokensBatch($batch, null, $this->currentUserId);

            // We could skip this, but a check before starting the batch is better
            $select = $this->resultFetcher->getSelect('gems__comm_jobs');
            $select->columns([
               'gcj_id_job',
            ])->where
                ->greaterThan('gcj_active', 0)
                ->equalTo('gcj_id_job', $jobId);

            $job = $this->resultFetcher->fetchOne($select);

            if (!empty($job)) {
                $batch->addTask('Comm\\ExecuteCommJobTask', $job, null, null, $preview);
            } else {
                $batch->reset();
                $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
                $messenger->addMessage($this->_("Messagejob is inactive and won't be executed"), 'danger');
            }

            if ($preview === true) {
                $batch->autoStart = true;
            }
        }

        if ($preview === true) {
            $title = sprintf($this->_('Preview single message job %s'), $jobId);
        } else {
            $title = sprintf($this->_('Executing single message job %s'), $jobId);
        }

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        return $batchRunner->getResponse($this->request);
    }

    /**
     * Execute all message jobs
     */
    public function executeAllAction()
    {
        $session = $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $batch = new TaskRunnerBatch('commjob-execute-all', $this->overloader, $session);
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        if ($this->cronLog instanceof LoggerInterface) {
            $batch->setMessageLogger($this->cronLog);
        }

        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (!$batch->isLoaded()) {
            $batch->addMessage($this->_('Execute all message jobs'));

            // Check for unprocessed tokens
            $this->tracker->loadCompletedTokensBatch($batch, null, $this->currentUserId);

            // We could skip this, but a check before starting the batch is better
            $select = $this->resultFetcher->getSelect('gems__comm_jobs');
            $select->columns([
                'gcj_id_job',
            ])->where
                ->greaterThan('gcj_active', 0);

            $job = $this->resultFetcher->fetchOne($select);

            if (!empty($job)) {
                $batch->addTask('Comm\\ExecuteCommJobTask', $job, null, null);
            } else {
                $batch->reset();
                $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
                $messenger->addMessage($this->_("Messagejob is inactive and won't be executed"), 'danger');
            }

            $batch->autoStart = true;
        }

        $title = $this->_('Executing all message job');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        return $batchRunner->getResponse($this->request);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Automatic message jobs');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('automatic messaging job', 'automatic messaging jobs', $count);
    }

    /**
     * @inheritdoc
     */
    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof CommJobBrowseSearchAction) {
            $action->extraSort['gcj_id_order'] = SORT_ASC;

            $action->searchFields['gcj_id_communication_messenger'] = $this->_('(any communication method)');
            $action->searchFields['gcj_id_message'] = $this->_('(all templates)');
            $action->searchFields[] = Html::br();
            $action->searchFields['gcj_active'] = $this->_('(all execution methods)');
            $action->searchFields['gcj_target'] = $this->_('(all fillers)');
        }

        if ($action instanceof CommJobShowAction) {
            $jobId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID);
            if (null !== $jobId) {
                $jobId = (int) $jobId;
                $job   = $this->commJobRepository->getJob($jobId);

                // Show a different color when not active,
                switch ($job['gcj_active']) {
                    case 0:
                        $class   = ' disabled';
                        $caption = $this->_('Message job inactive, can not be sent');
                        break;

                    case 2:
                        $class = ' manual';
                        $caption = $this->_('Message job manual, can only be sent using run');
                        break;

                    // gcj_active = 1
                    default:
                        $class = '';
                        $caption = $this->_('Message job automatic, can be sent using run or run all');
                        break;
                }
                $action->tokenParams = [
                    'model'           => $this->tracker->getTokenModel(),
                    'searchFilter'    => $this->commJobRepository->getJobFilter($job),
                    'class'           => 'browser table compliance mailjob' . $class,
                    'caption'         => $caption,
                    'onEmpty'         => $this->_('No tokens found for message'),
                    'extraSort'       => ['gto_valid_from' => SORT_ASC, 'gto_round_order' => SORT_ASC]
                ];
            }
        }
    }

    /**
     * Execute a single message job
     */
    public function previewAction()
    {
        $this->executeAction(true);
    }
}
