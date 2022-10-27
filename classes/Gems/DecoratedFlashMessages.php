<?php

namespace Gems;

use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;

class DecoratedFlashMessages implements DecoratedFlashMessagesInterface
{
    public const FLASH_KEY = 'action-messages';
    public const VALIDATION_KEY = 'validation-messages';

    public const TYPE_SUCCESS = 'success';
    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DANGER = 'danger';

    public const TYPES = [
        self::TYPE_SUCCESS,
        self::TYPE_INFO,
        self::TYPE_WARNING,
        self::TYPE_DANGER,
    ];

    private SessionInterface $session;

    private string $sessionKey;

    private readonly FlashMessagesInterface $base;

    private function __construct(SessionInterface $session, string $sessionKey)
    {
        $this->session    = $session;
        $this->sessionKey = $sessionKey;

        $this->base = FlashMessages::createFromSession($session, $sessionKey);
    }

    /**
     * Add a message to display on the next request
     */
    public function flashMessage(string $type, string $message): void
    {
        $this->flashMessages($type, [$message]);
    }

    /**
     * Add messages to display on the next request
     */
    public function flashMessages(string $type, array $messages): void
    {
        if (!in_array($type, self::TYPES)) {
            throw new \LogicException('Invalid flash type "' . $type . '"');
        }

        $storedFlash = $this->getStoredMessages();
        $messageList = $storedFlash[self::FLASH_KEY]['value'] ?? [];

        $messageList = array_merge(
            $messageList,
            array_map(fn ($x) => [$x, $type], $messages),
        );

        $this->flash(self::FLASH_KEY, $messageList);
    }

    public function flashValidationError(string $field, string $message): void
    {
        $this->flashValidationErrors($field, [$message]);
    }

    public function flashValidationErrors(string $field, array $messages): void
    {
        $storedFlash = $this->getStoredMessages();
        $messageList = $storedFlash[self::VALIDATION_KEY]['value'] ?? [];

        $messageList[$field] = array_merge(
            $messageList[$field] ?? [],
            $messages,
        );

        $this->flash(self::VALIDATION_KEY, $messageList);
    }

    public function hasFieldValidationErrors(string $field): bool
    {
        return array_key_exists($field, $this->getFlash(self::VALIDATION_KEY));
    }

    public function getFieldValidationErrors(string $field): array
    {
        return $this->getFlash(self::VALIDATION_KEY)[$field] ?? [];
    }

    public function flashSuccess(string $message): void
    {
        $this->flashMessage(self::TYPE_SUCCESS, $message);
    }

    public function flashSuccesses(array $messages): void
    {
        $this->flashMessages(self::TYPE_SUCCESS, $messages);
    }

    public function flashInfo(string $message): void
    {
        $this->flashMessage(self::TYPE_INFO, $message);
    }

    public function flashInfos(array $messages): void
    {
        $this->flashMessages(self::TYPE_INFO, $messages);
    }

    public function flashWarning(string $message): void
    {
        $this->flashMessage(self::TYPE_WARNING, $message);
    }

    public function flashWarnings(array $messages): void
    {
        $this->flashMessages(self::TYPE_WARNING, $messages);
    }

    public function flashDanger(string $message): void
    {
        $this->flashMessage(self::TYPE_DANGER, $message);
    }

    public function flashDangers(array $messages): void
    {
        $this->flashMessages(self::TYPE_DANGER, $messages);
    }

    public function flashError(string $message): void
    {
        $this->flashDanger($message);
    }

    public function flashErrors(array $messages): void
    {
        $this->flashDangers($messages);
    }

    /**
     * Add a message to display on the current request
     */
    public function appendMessage(string $type, string $message): void
    {
        $this->appendMessages($type, [$message]);
    }

    /**
     * Add messages to display on the current request
     */
    public function appendMessages(string $type, array $messages): void
    {
        if (!in_array($type, self::TYPES)) {
            throw new \LogicException('Invalid flash type "' . $type . '"');
        }

        $storedFlash = $this->getFlashes();
        $messageList = $storedFlash[self::FLASH_KEY] ?? [];

        $messageList = array_merge(
            $messageList,
            array_map(fn ($x) => [$x, $type], $messages),
        );

        $this->flashNow(self::FLASH_KEY, $messageList, 0);
    }

    public function appendSuccess(string $message): void
    {
        $this->appendMessage(self::TYPE_SUCCESS, $message);
    }

    public function appendSuccesses(array $messages): void
    {
        $this->appendMessages(self::TYPE_SUCCESS, $messages);
    }

    public function appendInfo(string $message): void
    {
        $this->appendMessage(self::TYPE_INFO, $message);
    }

    public function appendInfos(array $messages): void
    {
        $this->appendMessages(self::TYPE_INFO, $messages);
    }

    public function appendWarning(string $message): void
    {
        $this->appendMessage(self::TYPE_WARNING, $message);
    }

    public function appendWarnings(array $messages): void
    {
        $this->appendMessages(self::TYPE_WARNING, $messages);
    }

    public function appendDanger(string $message): void
    {
        $this->appendMessage(self::TYPE_DANGER, $message);
    }

    public function appendDangers(array $messages): void
    {
        $this->appendMessages(self::TYPE_DANGER, $messages);
    }

    public function appendError(string $message): void
    {
        $this->appendDanger($message);
    }

    public function appendErrors(array $messages): void
    {
        $this->appendDangers($messages);
    }

    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = self::FLASH_NEXT
    ): DecoratedFlashMessagesInterface {
        return new self($session, $sessionKey);
    }

    public function flash(string $key, $value, int $hops = 1): void
    {
        $this->base->flash($key, $value, $hops);
    }

    public function flashNow(string $key, $value, int $hops = 1): void
    {
        $this->base->flashNow($key, $value, $hops);
    }

    public function getFlash(string $key, $default = null)
    {
        return $this->base->getFlash($key, $default);
    }

    public function getFlashes(): array
    {
        return $this->base->getFlashes();
    }

    public function clearFlash(): void
    {
        $this->base->clearFlash();
    }

    public function prolongFlash(): void
    {
        $this->base->prolongFlash();
    }

    private function getStoredMessages(?string $sessionKey = null): array
    {
        $messages = $this->session->get($sessionKey ?? $this->sessionKey, []);
        return $messages ?? [];
    }
}
