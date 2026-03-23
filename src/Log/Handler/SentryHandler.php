<?php

declare(strict_types=1);

namespace Gems\Log\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Sentry\Event;
use Sentry\EventHint;
use Throwable;

use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\init;

class SentryHandler extends AbstractProcessingHandler
{
    private const REDACTED = '[redacted]';

    /**
     * @var string[]
     */
    private const SAFE_KEYS = [
        '_active',
        '_changed',
        '_class',
        '_code',
        '_created',
        '_id',
        '_opened',
        '_order',
        '_status',
        '_type',
    ];

    private const SCRUB_PATTERNS = [
        'scrubbed-ipv4' => '/\b(?:(?:\d{1,3})\.){3}(?:\d{1,3})\b/',
        'scrubbed-ipv6' => '/(?<![A-F0-9:])(?:(?:[A-F0-9]{1,4}:){4,7}[A-F0-9]{1,4}|(?=[A-F0-9:]*::)(?=(?:[^:]*:){4,})(?:[A-F0-9]{1,4}(?::[A-F0-9]{1,4}){0,6})?::(?:[A-F0-9]{1,4}(?::[A-F0-9]{1,4}){0,6})?)(?![A-F0-9:])/i',
        'scrubbed-email' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
        'scrubbed-hash' => '/\$2y\$(\S[^,]*)/',
    ];

    private bool $enabled = false;

    public function __construct(
        Level $level,
        array $options = []
    ) {
        parent::__construct($level);

        $dsn = $options['dsn'] ?? ($_ENV['SENTRY_DSN'] ?? null);

        if (empty($dsn) || !\function_exists('Sentry\\init')) {
            return;
        }

        try {
            init([
                'dsn' => $dsn,
                'environment' => $options['environment'] ?? ($_ENV['APP_ENV'] ?? null),
                'release' => $options['release'] ?? ($_ENV['APP_VERSION'] ?? null),
                'before_send' => static fn (Event $event, ?EventHint $hint): Event => self::scrubEvent($event, $hint),
                'traces_sample_rate' => (float) ($options['traces_sample_rate'] ?? 0.0),
                'send_default_pii' => (bool) ($options['send_default_pii'] ?? false),
            ]);
            $this->enabled = true;
        } catch (Throwable) {
            // Never let external logging failures break local logging.
        }
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $exception = $record->context['exception'] ?? null;
            if ($exception instanceof Throwable) {
                captureException($exception);
                return;
            } else {
                captureMessage(sprintf('[%s] %s', $record->level->getName(), (string) $record->message));
            }
        } catch (Throwable) {
            // Never let external logging failures break local logging.
        }
    }

    private static function scrubEvent(Event $event, EventHint $hint): Event
    {
        $event->setRequest(self::scrubRequest($event->getRequest()));

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::scrubString($exception->getValue()));
        }

        return $event;
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private static function scrubRequest(array $request): array
    {
        if (!array_key_exists('data', $request)) {
            return $request;
        }

        $requestData = $request['data'];
        if (is_array($requestData)) {
            $request['data'] = self::scrubArray($requestData);
        } elseif ($requestData !== null) {
            $request['data'] = self::REDACTED;
        }

        return $request;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private static function scrubArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (empty($value) || is_bool($value)) {
                continue;
            }

            if (is_string($value)) {
                $scrubbedValue = self::scrubString($value);
                if ($scrubbedValue !== $value) {
                    $data[$key] = $scrubbedValue;
                    continue;
                }
            }

            $keyString = strtolower((string) $key);
            if (!self::containsSafeKey($keyString)) {
                $data[$key] = self::REDACTED;
                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::scrubArray($value);
                continue;
            }

            if (is_object($value)) {
                $data[$key] = self::REDACTED;
            }
        }

        return $data;
    }

    private static function containsSafeKey(string $key): bool
    {
        foreach (self::SAFE_KEYS as $safeKey) {
            if (str_contains($key, $safeKey)) {
                return true;
            }
        }

        return false;
    }

    private static function scrubString(string $value): string
    {
        foreach (self::SCRUB_PATTERNS as $replacement => $pattern) {
            $value = preg_replace($pattern, "[$replacement]", $value) ?? $value;
        }

        return (string) $value;
    }
}
