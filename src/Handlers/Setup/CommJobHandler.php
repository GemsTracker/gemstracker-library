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
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Menu\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Model\CommJobModel;
use Gems\Repository\CommJobRepository;
use Gems\Snippets\Communication\CommJobButtonRowSnippet;
use Gems\Snippets\Communication\CommJobIndexButtonRowSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker;
use Gems\Util\Lock\CommJobLock;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionMiddleware;
use MUtil\Model;
use MUtil\Model\ModelAbstract;
use Psr\Log\LoggerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\DependencyResolver\ConstructorDependencyResolver;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\StatusMessengerInterface;
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
class CommJobHandler extends ModelSnippetLegacyHandlerAbstract
{
    protected array $autofilterParameters = [
        'extraSort'    => ['gcj_id_order' => SORT_ASC],
        'searchFields' => 'getSearchFields'
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    //protected array $createEditSnippets = ['ModelFormVariableFieldSnippet'];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * @var array
     */
    public $config;

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

    protected $monitorParameters = [
        'monitorJob' => 'getMailMonitorJob'
    ];

    protected $monitorSnippets = 'MonitorSnippet';

    /**
     * Query to get the round descriptions for options
     * @var string
     */
    protected string $roundDescQuery = "SELECT gro_round_description, gro_round_description FROM gems__rounds WHERE gro_id_track = ? GROUP BY gro_round_description";

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
        TranslatorInterface $translate,
        protected ResultFetcher $resultFetcher,
        protected ProjectOverloader $overloader,
        protected Tracker $tracker,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected CommJobRepository $commJobRepository,
        protected CommJobLock $communicationJobLock,
        protected RouteHelper $routeHelper,
    )
    {
        parent::__construct($responder, $translate);
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
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var $model CommJobModel
         */

        $model = $this->overloader->create(CommJobModel::class);

        if ($detailed) {
            $model->applyDetailSettings();
            if ($action == 'create') {
                // Set the default round order
                $newOrder = $this->resultFetcher->fetchOne('SELECT MAX(gcj_id_order) FROM gems__comm_jobs');

                if ($newOrder) {
                    $model->set('gcj_id_order', 'default', $newOrder + 10);
                }
            }
        }

        return $model;
    }

    public function lockAction()
    {
        if ($this->communicationJobLock->isLocked()) {
            $this->communicationJobLock->unlock();

            /**
             * @var $messenger StatusMessengerInterface
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
        $jobId = intval($this->request->getAttribute(Model::REQUEST_ID));
        $session = $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $batch = new TaskRunnerBatch($jobId, $this->overloader, $session);
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
            $this->tracker->loadCompletedTokensBatch($batch, null, $this->currentUser->getUserId());

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
        $batch = $this->commJobRepository->getCronBatch(
            'commjob-execute-all',
            $this->overloader,
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($this->_('Execute all message jobs'));
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

    public function getMailMonitorJob()
    {
        //return $this->loader->getUtil()->getMonitor()->getCronMailMonitor();
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
             * @var $messenger StatusMessengerInterface
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

        parent::indexAction();

        $this->html->pInfo($this->_('With automatic messaging jobs and a cron job on the server, messages can be sent without manual user action.'));
    }

    public function monitorAction() {
        if ($this->monitorSnippets) {
            $params = $this->_processParameters($this->monitorParameters);

            $this->addSnippets($this->monitorSnippets, $params);
        }
    }

    /**
     * Execute a single message job
     */
    public function previewAction() {
        $this->executeAction(true);
    }

    /**
     * Ajax return function for round selection
     */
    public function roundselectAction()
    {
        $trackId = $this->requestInfo->getParam('sourceValue');
        $rounds = $this->resultFetcher->fetchPairs($this->roundDescQuery, [$trackId]);

        return new JsonResponse($rounds);
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction()
    {
        parent::showAction();

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
            $this->addSnippet('TokenPlanTableSnippet', $params);
        }
    }

    public function sortAction()
    {
        //$this->_helper->getHelper('SortableTable')->ajaxAction('gems__comm_jobs','gcj_id_job', 'gcj_id_order');
    }
}
