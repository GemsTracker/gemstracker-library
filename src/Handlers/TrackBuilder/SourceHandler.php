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

use Gems\Audit\AuditLog;
use Gems\Batch\BatchRunnerLoader;
use Gems\Db\ResultFetcher;
use Gems\Encryption\ValueEncryptor;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Model\TrackBuilder\SourceModel;
use Gems\Tracker;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use MUtil\Legacy\RequestHelper;
use MUtil\Model\ModelAbstract;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * Controller for Source maintenance
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class SourceHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterParameters = [
        'extraSort'   => ['gso_source_name' => SORT_ASC],
    ];

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['source', 'sources',];

    protected int $currentUserId = UserLoader::UNKNOWN_USER_ID;

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
    public array $summarizedActions = ['index', 'autofilter', 'check-all', 'attributes-all', 'synchronize-all'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        CurrentUserRepository $currentUserRepository,
        protected Tracker $tracker,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected ResultFetcher $resultFetcher,
        protected RouteHelper $routeHelper,
        protected Translated $translatedUtil,
        protected AuditLog $accesslog,
        protected ValueEncryptor $valueEncryptor,
        protected readonly SourceModel $sourceModel,

    ) {
        parent::__construct($responder, $translate, $cache);

        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * Displays a textual explanation what synchronization does on the page.
     */
    protected function addSynchronizationInformation()
    {
        $this->html->pInfo($this->_(
            'Check source for new surveys, changes in survey status and survey deletion. Can also perform maintenance on some sources, e.g. by changing the number of attributes.'
        ));
        $this->html->pInfo($this->_(
            'Run this code when the status of a survey in a source has changed or when the code has changed and the source must be adapted.'
        ));
    }

    /**
     * Check token attributes for a single source
     */
    public function attributesAction()
    {
        $sourceId = $this->getSourceId();

        $where    = 'gsu_id_source = ?';

        $session = $this->request->getAttribute(SessionInterface::class);
        $batch = $this->tracker->refreshTokenAttributes($session, 'attributeCheck', $where, $sourceId);
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = sprintf($this->_('Refreshing token attributes for %s source.'),
            $this->resultFetcher->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", [$sourceId]));

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_(
                'Refreshes the attributes for a token as stored in the source.'
            ),
            $this->_(
                'Run this code when the number of attributes has changed or when you suspect the attributes have been corrupted somehow.'
            ),
        ]);
        return $batchRunner->getResponse($this->request);
    }

    /**
     * Check all token attributes for all sources
     */
    public function attributesAllAction()
    {
        $session = $this->request->getAttribute(SessionInterface::class);
        $batch = $this->tracker->refreshTokenAttributes($session, 'attributeCheckAll');
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = $this->_('Refreshing token attributes for all sources.');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_(
                'Refreshes the attributes for a token as stored in one of the sources.'
            ),
            $this->_(
                'Run this code when the number of attributes has changed or when you suspect the attributes have been corrupted somehow.'
            ),
        ]);
        return $batchRunner->getResponse($this->request);
    }

    /**
     * Check all the tokens for a single source
     */
    public function checkAction()
    {
        $sourceId = $this->getSourceId();

        $batch = $this->tracker->recalculateTokens(
            $this->request->getAttribute(SessionInterface::class),
            'sourceCheck' . $sourceId,
            $this->currentUserId,
            [sprintf('gto_id_survey IN (SELECT gsu_id_survey FROM gems__surveys WHERE gsu_id_source = %d)', $sourceId)]
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = sprintf($this->_('Checking all surveys in the %s source for answers.'),
            $this->resultFetcher->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", [$sourceId]));


        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_(
                'Check tokens for being answered or not, reruns survey and round event code on completed tokens and recalculates the start and end times of all tokens in tracks that have completed tokens.'
            ),
            $this->_(
                'Run this code when survey result fields, survey or round events or the event code has changed or after bulk changes in a survey source.'
            ),
            $this->_('This task checks all tokens using this source for answers .'),
        ]);
        return $batchRunner->getResponse($this->request);
    }

    /**
     * Check all the tokens for all sources
     */
    public function checkAllAction()
    {
        $batch = $this->tracker->recalculateTokens(
            $this->request->getAttribute(SessionInterface::class),
            'surveyCheckAll',
            $this->currentUserId
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = $this->_('Checking all surveys for all sources for answers.');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_(
                'Check tokens for being answered or not, reruns survey and round event code on completed tokens and recalculates the start and end times of all tokens in tracks that have completed tokens.'
            ),
            $this->_(
                'Run this code when survey result fields, survey or round events or the event code has changed or after bulk changes in a survey source.'
            ),
            $this->_('This task checks all tokens in all sources for answers.'),
        ]);
        return $batchRunner->getResponse($this->request);
    }

    public function createModel(bool $detailed, string $action): SourceModel
    {
        if ($detailed) {
            $this->sourceModel->applyDetailSettings($action);
        }

        return $this->sourceModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Survey Sources');
    }

    /**
     * Load a source object
     *
     * @param int $sourceId
     * @return \Gems\Tracker\Source\SourceInterface
     */
    private function getSourceById($sourceId = null)
    {
        if (null === $sourceId) {
            $sourceId = $this->getSourceId();
        }
        return $this->tracker->getSource($sourceId);
    }

    /**
     * The id of the current source
     *
     * @return int
     */
    private function getSourceId(): int
    {
        $sourceId = intval($this->request->getAttribute(\MUtil\Model::REQUEST_ID));

        return $sourceId;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('source', 'sources', $count);
    }

    /**
     * Action to check whether the source is active
     */
    public function pingAction()
    {
        $source = $this->getSourceById();

        /**
         * @var StatusMessengerInterface $messenger
         */
        $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        try {
            if ($source->checkSourceActive($this->currentUserId)) {
                $message = $this->_('This installation is active.');
                $status  = 'success';
                $messenger->addSuccess($message);
            } else {
                $message = $this->_('Inactive installation.');
                $status  = 'warning';
                $messenger->addWarning($message);
            }
            $this->accesslog->logChange($this->request, $message, [$status]);
        } catch (\Exception $e) {
            $messenger->addDanger($this->_('Installation error!'));
            $messenger->addDanger($e->getMessage());
        }

        $requestHelper = new RequestHelper($this->request);
        $currentRoute = $requestHelper->getRouteResult();
        $showRoute = $this->routeHelper->getRouteSibling($currentRoute->getMatchedRouteName(), 'show');
        $params = $currentRoute->getMatchedParams();
        return new RedirectResponse($this->routeHelper->getRouteUrl($showRoute['name'], $params));
    }

    /**
     * Synchronize survey status for the surveys in a source
     */
    public function synchronizeAction()
    {
        $sourceId = $this->getSourceId();

        $session = $this->request->getAttribute(SessionInterface::class);

        $batch = $this->tracker->synchronizeSources($session, $sourceId);
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $title = sprintf($this->_('Synchronize the %s source.'),
            $this->resultFetcher->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", [$sourceId]));

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_(
                'Check source for new surveys, changes in survey status and survey deletion. Can also perform maintenance on some sources, e.g. by changing the number of attributes.'
            ),
            $this->_(
                'Run this code when the status of a survey in a source has changed or when the code has changed and the source must be adapted.'
            ),
        ]);
        return $batchRunner->getResponse($this->request);
    }

    /**
     * Synchronize survey status for the surveys in all sources
     */
    public function synchronizeAllAction()
    {
        $session = $this->request->getAttribute(SessionInterface::class);
        $batch = $this->tracker->synchronizeSources($session);
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batch->minimalStepDurationMs = 3000;

        $title = $this->_('Synchronize all sources.');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        return $batchRunner->getResponse($this->request);
    }
}
