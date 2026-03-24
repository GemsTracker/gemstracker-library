<?php

declare(strict_types=1);

namespace Sentry;

class Event
{
    /** @return array<string,mixed> */
    public function getRequest(): array { return []; }

    /** @param array<string,mixed> $request */
    public function setRequest(array $request): void {}

    /** @return array<int, object> */
    public function getExceptions(): array { return []; }
}

class EventHint {}

function init(array $options = []): void {}
function captureException(\Throwable $exception): void {}
function captureMessage(string $message): void {}
