<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Handlers\Setup\CommunicationActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Communication;

use Gems\Handlers\Setup\CommunicationActions\CommJobExecuteAllAction;
use Gems\Menu\MenuSnippetHelper;
use Gems\Messenger\Message\CommJob;
use Gems\Repository\CommJobRepository;
use Gems\Util\Lock\MaintenanceLock;
use Gems\Util\Monitor\Monitor;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\MessageableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Handlers\Setup\CommunicationActions
 * @since      Class available since version 1.0
 */
class CommJobExecuteBatchSnippet extends MessageableSnippetAbstract
{
    protected string $formTitle = '';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly CommJobRepository $commJobRepository,
        protected readonly CommJobExecuteAllAction $commJobExecuteAllAction,
        protected readonly MaintenanceLock $maintenanceLock,
        protected readonly MenuSnippetHelper $menuHelper,
        protected readonly MessageBusInterface $messageBus,
        protected readonly Monitor $monitor,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);
    }

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();
        $html->h3($this->formTitle, array('class' => 'title'));

        $cancelLabel = $this->_('Cancel');
        if ($this->maintenanceLock->isLocked()) {
            $this->addMessage($this->_('Cannot execute mail job, system is in maintenance mode.'));
        } else {
            $jobId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID, false);
            $jobs = [];
            if ($jobId) {
                $job = $this->commJobRepository->getJob((int)$jobId);
                if (0 == $job['gcj_active']) {
                    $this->addMessage($this->_('This job is disabled and cannot be executed.'));
                } else {
                    $jobs[] = $job;
                }
                $cancelLabel = $this->_('Show');

            } else {
                $jobs = $this->commJobRepository->getActiveJobs();
            }
            if ($jobs) {
                $preview = (bool) $this->requestInfo->getParam('preview', false);
                $ol = $html->ul();
                foreach($jobs as $jobData) {
                    $commJobMessage = new CommJob($jobData, $preview);
                    $envelope = $this->messageBus->dispatch($commJobMessage);
                    /**
                     * @var HandledStamp $stamp
                     */
                    $stamp = $envelope->last(HandledStamp::class);
                    $ol->append($stamp->getResult());
                }
                if (! ($jobId || $preview)) {
                    $this->monitor->startCronMailMonitor();
                }
            }
        }

        $url = [$this->menuHelper->getCurrentUrl(), 'step' => commJobExecuteAllAction::STEP_RESET];
        $html->a($url, $this->_('Reset'), ['class' => 'btn actionLink']);
        $html->br();
        $html->a([$this->menuHelper->getCurrentParentUrl()], $cancelLabel, ['class' => 'btn actionLink']);

        return $html;
    }

    public function hasHtmlOutput(): bool
    {
        return commJobExecuteAllAction::STEP_BATCH === $this->commJobExecuteAllAction->step;
    }
}