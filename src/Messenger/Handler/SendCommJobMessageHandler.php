<?php

namespace Gems\Messenger\Handler;

use Gems\Messenger\Message\SendCommJobMessage;
use Gems\Repository\CommJobRepository;
use Gems\Tracker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendCommJobMessageHandler
{
    public function __construct(
        private readonly CommJobRepository $commJobRepository,
        private Tracker $tracker,
    )
    {}

    public function __invoke(SendCommJobMessage $message)
    {
        $jobId = $message->getJobId();
        $jobData = $this->commJobRepository->getJob($jobId);

        $token = $this->tracker->getToken($message->getTokenId());

        $messenger = $this->commJobRepository->getJobMessenger($jobData['gcm_type']);
        $messenger->sendCommunication($jobData, $token, false);
    }
}