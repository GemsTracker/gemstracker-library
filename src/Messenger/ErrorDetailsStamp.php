<?php

declare(strict_types=1);

namespace Gems\Messenger;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\StampInterface;

// Same as Symfony, but with added stacktrace. Also flattenException is not supported
final class ErrorDetailsStamp implements StampInterface
{
    private string $exceptionClass;
    private int|string $exceptionCode;
    private string $exceptionMessage;
    private string $exceptionTrace;

    public function __construct(string $exceptionClass, int|string $exceptionCode, string $exceptionMessage, ?string $exceptionTrace = null)
    {
        $this->exceptionClass = $exceptionClass;
        $this->exceptionCode = $exceptionCode;
        $this->exceptionMessage = $exceptionMessage;
        $this->exceptionTrace = $exceptionTrace;
    }

    public static function create(\Throwable $throwable): self
    {
        if ($throwable instanceof HandlerFailedException) {
            $throwable = $throwable->getPrevious();
        }

        return new self($throwable::class, $throwable->getCode(), $throwable->getMessage(), $throwable->getTraceAsString());
    }

    public function getExceptionClass(): string
    {
        return $this->exceptionClass;
    }

    public function getExceptionCode(): int|string
    {
        return $this->exceptionCode;
    }

    public function getExceptionMessage(): string
    {
        return $this->exceptionMessage;
    }

    public function getExceptionTrace(): string
    {
        return $this->exceptionTrace;
    }

    public function equals(?self $that): bool
    {
        if (null === $that) {
            return false;
        }

        return $this->exceptionClass === $that->exceptionClass
            && $this->exceptionCode === $that->exceptionCode
            && $this->exceptionMessage === $that->exceptionMessage;
    }
}