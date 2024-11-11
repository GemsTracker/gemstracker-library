<?php

namespace Gems\Messenger\Handler;

use Gems\Communication\CommunicationRepository;
use Gems\Messenger\Message\CommJob;
use Gems\Messenger\Message\SendCommJobMessage;
use Gems\Messenger\Message\SendTokenMessage;
use Gems\Repository\CommJobRepository;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Monitor\Monitor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class CommJobHandler
{
    public function __construct(
        protected CommJobRepository $commJobRepository,
        protected CommunicationRepository $communicationRepository,
        protected readonly MaskRepository $maskRepository,
        protected MessageBusInterface $messageBus,
        protected readonly Monitor $monitor,
    )
    {}

    public function __invoke(CommJob $commJob): string
    {
        $this->maskRepository->disableMaskRepository();
        $tokensNested = $this->commJobRepository->getSendableTokensNested($commJob->getId(), forced: $commJob->isForced());

        $allTokenCount = 0;
        foreach ($tokensNested as $firstToken => $allTokens) {
            $allTokenCount++;
            $allTokenCount += count($allTokens);

            if (!$this->commJobRepository->isTokenInQueue($firstToken)) {
                $message = new SendTokenMessage($commJob->getId(), $firstToken, $allTokens, $commJob->isPreview(), $commJob->isForced());

                $this->commJobRepository->setTokenIsInQueue($firstToken);
                foreach ($allTokens as $token) {
                    $this->commJobRepository->setTokenIsInQueue($token);
                }
                $this->messageBus->dispatch($message);
            }
        }
        $this->monitor->startCronMailMonitor();
        $this->maskRepository->enableMaskRepository();

        $templateName = $this->communicationRepository->getTemplateName($commJob->getTemplateId());
        $sendCount    = count($tokensNested);
        if ($commJob->isPreview()) {
            return sprintf(
                "Job %d, order %s, preview: %d communications would have been sent with template '%s', changing %d tokens.",
                $commJob->getId(),
                $commJob->getOrder(),
                $sendCount,
                $templateName,
                $allTokenCount
            );
        }

        return sprintf(
            "Job id %d, order %s: %d communications sent with template '%s', changed %d tokens.",
            $commJob->getId(),
            $commJob->getOrder(),
            $sendCount,
            $templateName,
            $allTokenCount
        );
//        $sendableTokens = $this->commJobRepository->getSendableTokens($commJob->getId());
//
//        foreach($sendableTokens['send'] as $sendableTokenId) {
//            if (!$this->commJobRepository->isTokenInQueue($sendableTokenId)) {
//                $message = new SendCommJobMessage($commJob->getId(), $sendableTokenId);
//                $this->commJobRepository->setTokenIsInQueue($sendableTokenId);
//                $this->messageBus->dispatch($message);
//            }
//
//        }
//
//        foreach($sendableTokens['markSent'] as $sendableTokenId) {
//
//            if (!$this->commJobRepository->isTokenInQueue($sendableTokenId)) {
//                $message = new SendCommJobMessage($commJob->getId(), $sendableTokenId);
//                $this->commJobRepository->setTokenIsInQueue($sendableTokenId);
//                $this->messageBus->dispatch($message);
//            }
//        }
    }
}