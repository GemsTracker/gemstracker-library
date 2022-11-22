<?php

namespace Gems\Session;

use Mezzio\Flash\FlashMessagesInterface;

class ValidationMessenger
{
    public const VALIDATION_FLASH_KEY = 'validation-messages';

    public function __construct(protected FlashMessagesInterface $flash)
    {}

    public function addValidationError(string $field, string $message): void
    {
        $this->addValidationErrors($field, [$message]);
    }

    public function addValidationErrors(string $field, array $messages): void
    {
        $messageList = $this->flash->getFlash(static::VALIDATION_FLASH_KEY, []);
        $messageList[$field] = array_merge(
            $messageList[$field] ?? [],
            $messages,
        );

        $this->flash->flash(static::VALIDATION_FLASH_KEY, $messageList);
    }

    public function hasFieldValidationErrors(string $field): bool
    {
        return array_key_exists($field, $this->flash->getFlash(self::VALIDATION_FLASH_KEY, []));
    }

    public function getFieldValidationErrors(string $field): array
    {
        return $this->flash->getFlash(self::VALIDATION_FLASH_KEY)[$field] ?? [];
    }
}