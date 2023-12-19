<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Messenger\Handler
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Messenger\Handler;

use Gems\Event\Application\TokenMarkedAsSent;
use Gems\Messenger\Message\SendTokenMessage;
use Gems\Repository\CommJobRepository;
use Gems\Tracker;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @package    Gems
 * @subpackage Messenger\Handler
 * @since      Class available since version 1.0
 */
#[AsMessageHandler]
class SendTokenMessageHandler
{
    public function __construct(
        private readonly CommJobRepository $commJobRepository,
        private readonly Tracker $tracker,
        protected readonly EventDispatcherInterface $eventDispatcher,
    )
    {}

    public function __invoke(SendTokenMessage $message)
    {
        $job     = $message->getJob();
        $jobId   = $job->getId();
        $jobData = $this->commJobRepository->getJob($jobId);

        $token = $this->tracker->getToken($message->getTokenId());

        $messenger = $this->commJobRepository->getJobMessenger($jobData['gcm_type']);
        $messenger->sendCommunication($jobData, $token, $job->isPreview());

        if (! $job->isPreview()) {
            foreach ($message->getMarkedTokens() as $tokenId) {
                $token = $this->tracker->getToken($tokenId);
                $event = new TokenMarkedAsSent($token, $jobData);

                $this->eventDispatcher->dispatch($event);
            }
        }
    }
}