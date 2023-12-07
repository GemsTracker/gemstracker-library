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
use Gems\Handlers\BrowseChangeHandler;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Model\Setup\CommJobModel;
use Gems\Repository\CommJobRepository;
use Gems\Snippets\Communication\CommJobButtonRowSnippet;
use Gems\Snippets\Communication\CommJobIndexButtonRowSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\SnippetsActions\Monitor\CommJobMonitorAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker;
use Gems\Util\Lock\CommJobLock;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionMiddleware;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
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
class CommJobHandler extends BrowseChangeHandler
{
    use ConstructorModelHandlerTrait;

    /**
     * @inheritdoc 
     */
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'create'     => CreateAction::class,
        'export'     => ExportAction::class,
        'edit'       => EditAction::class,
        'delete'     => DeleteAction::class,
        'show'       => ShowAction::class,
        'monitor'    => CommJobMonitorAction::class,
    ];

    protected array $autofilterParameters = [
        'extraSort'    => ['gcj_id_order' => SORT_ASC],
        'searchFields' => 'getSearchFields'
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
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Agenda\\AutosearchFormSnippet'];

    protected array $indexStopSnippets = [CommJobIndexButtonRowSnippet::class];

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
        protected readonly CommJobLock $communicationJobLock,
        protected readonly RouteHelper $routeHelper,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);

        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->model = $this->commJobModel;
    }

    public function lockAction()
    {
        if ($this->communicationJobLock->isLocked()) {
            $this->communicationJobLock->unlock();

            /**
             * @var StatusMessengerInterface $messenger
             */
            $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $messenger->clearMessages();
            $messenger->addSuccess($this->_('Cron jobs are active'));
        } else {
            $this->communicationJobLock->lock();
        }

        // Redirect
        return new RedirectResponse($this->routeHelper->getRouteUrl('setup.communication.job.index'));
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
     * Returns the fields for autosearch with
     *
     * @return array
     */
    public function getSearchFields()
    {
        return [
            'gcj_active' => $this->_('(all execution methods)')
        ];
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
     * Action for showing a browse page
     */
    public function indexAction()
    {
        if ($this->communicationJobLock->isLocked()) {
            /**
             * @var StatusMessengerInterface $messenger
             */
            $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $messenger->addError(
                sprintf(
                    $this->_('Automatic messaging have been turned off since %s.'),
                    $this->communicationJobLock->getLockTime()->format('H:i d-m-Y')
                ));

            /*if ($menuItem = $this->menu->findController('cron', 'cron-lock')) {
                $menuItem->set('label', $this->_('Turn Automatic Messaging Jobs ON'));
            }*/
        }

        // parent::indexAction();

        // $this->html->pInfo($this->_('With automatic messaging jobs and a cron job on the server, messages can be sent without manual user action.'));
    }

    /**
     * Execute a single message job
     */
    public function previewAction()
    {
        $this->executeAction(true);
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction()
    {
        // parent::showAction();

        $jobId = $this->request->getAttribute('id');
        if (!is_null($jobId)) {
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
            $model  = $this->tracker->getTokenModel();
            $filter = $this->commJobRepository->getJobFilter($job);
            $params = [
                'tokenModel'           => $model,
                'tokenFilter'          => $filter,
                'tokenShowActionLinks' => false,
                'tokenClass'           => 'browser table mailjob' . $class,
                'tokenCaption'         => $caption,
                'tokenOnEmpty'         => $this->_('No tokens found to message'),
                'tokenExtraSort'       => ['gto_valid_from' => SORT_ASC, 'gto_round_order' => SORT_ASC]
            ];
            // $this->addSnippet('TokenPlanTableSnippet', $params);
        }
    }
}
