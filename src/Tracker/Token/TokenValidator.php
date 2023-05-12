<?php

namespace Gems\Tracker\Token;

use Gems\Repository\TokenAttemptsRepository;
use Gems\Tracker;
use Laminas\Validator\ValidatorInterface;
use MUtil\Translate\Translator;
use DateTimeImmutable;

class TokenValidator implements ValidatorInterface
{
    protected string $clientIp;

    protected array $messages = [];

    public function __construct(
        protected TokenAttemptsRepository $tokenAttemptsRepository,
        protected Tracker $tracker,
        protected Translator $translator,
    )
    {}

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function isValid($value)
    {
        $this->tokenAttemptsRepository->prune();

        if ($this->tokenAttemptsRepository->checkBlock()) {
            $this->tokenAttemptsRepository->markAttemptsAsBlocked();
            $this->messages[] = $this->translator->_('The server is currently busy, please wait a while and try again.');
            // $this->logger->error("Possible token brute force attack, throttling for $remainingDelay seconds");
        }

        // The pure token check
        if ($this->isValidToken($value)) {
            return true;
        }

        $this->tokenAttemptsRepository->addAttempt($value, $this->clientIp);
        return false;
    }

    /**
     * Separate the incorrect tokens from the right tokens
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isValidToken(string $value): bool
    {
        // Make sure the value has the right format
        $value   = $this->tracker->filterToken($value);
        $library = $this->tracker->getTokenLibrary();
        $format  = $library->getFormat();
        $reuse   = $library->hasReuse() ? $library->getReuse() : -1;

        if (strlen($value) !== strlen($format)) {
            $this->messages[] = sprintf($this->translator->_('Not a valid token. The format for valid tokens is: %s.'), $format);
            return false;
        }

        $token = $this->tracker->getToken($value);
        if ($token && $token->exists && $token->getReceptionCode()->isSuccess()) {
            $currentDate = new DateTimeImmutable();

            if ($completionTime = $token->getCompletionTime()) {
                // Reuse means a user can use an old token to check for new surveys
                if ($reuse >= 0) {
                    // Oldest date AFTER completion date. Oldest date is today minus reuse time
                    if ($currentDate->diff($completionTime)->days <= $reuse) {
                        // It is completed and may still be used to look
                        // up other valid tokens.
                        return true;
                    }
                }
                $this->messages[] = $this->translator->_('This token is no longer valid.');
                return false;
            }

            $fromDate = $token->getValidFrom();
            if ((null === $fromDate) || ($currentDate->getTimestamp() < $fromDate->getTimestamp())) {
                // Current date is BEFORE from date
                $this->messages[] = $this->translator->_('This token cannot (yet) be used.');
                return false;
            }

            if ($untilDate = $token->getValidUntil()) {
                if ($currentDate->getTimestamp() > $untilDate->getTimestamp()) {
                    //Current date is AFTER until date
                    $this->messages[] = $this->translator->_('This token is no longer valid.');
                    return false;
                }
            }

            return true;
        } else {
            $this->messages[] = $this->translator->_('Unknown token.');
            return false;
        }
    }

    public function setClientIp(string $ipAddress): void
    {
        $this->clientIp = $ipAddress;
    }
}