<?php

namespace Gems\Repository;

use Gems\Legacy\CurrentUserRepository;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker;
use Mezzio\Session\SessionInterface;
use MUtil\Translate\Translator;
use Psr\Log\LoggerInterface;
use Zalt\Loader\ProjectOverloader;

class MailRepository
{
    protected int $currentUserId;

    public function __construct(
        protected Tracker $tracker,
        protected Translator $translator,
        CurrentUserRepository $currentUserRepository
    )
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * Perform automatic job mail
     */
    public function getCronBatch($id, ProjectOverloader $loader, SessionInterface $session, ?LoggerInterface $cronLog = null)
    {
        $batch = new TaskRunnerBatch($id, $loader, $session);
        if ($cronLog) {
            $batch->setMessageLogger($cronLog);
        }
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (! $batch->isLoaded()) {
            $this->loadCronBatch($batch);
        }

        return $batch;
    }

    /**
     * Perform the actions and load the tasks needed to start the cron batch
     *
     * @param TaskRunnerBatch $batch
     */
    protected function loadCronBatch(TaskRunnerBatch $batch)
    {
        $batch->addMessage(sprintf($this->translator->_("Starting mail jobs")));
        $batch->addTask('Mail\\AddAllMailJobsTask');

        // Check for unprocessed tokens,
        $this->tracker->loadCompletedTokensBatch($batch, null, $this->currentUserId);
    }

    public function getMailTargets(): array
    {
        return [
            'staff' => 'Staff',
            'respondent' => 'Respondent',
            'token' => 'Token',
            'staffPassword' => 'Password reset',
        ];
    }
}