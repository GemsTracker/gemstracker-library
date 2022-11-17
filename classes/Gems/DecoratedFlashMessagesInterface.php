<?php

namespace Gems;

use Mezzio\Flash\FlashMessagesInterface;

interface DecoratedFlashMessagesInterface extends FlashMessagesInterface
{
    public function flashMessage(string $type, string $message): void;

    public function flashMessages(string $type, array $messages): void;

    public function flashValidationError(string $field, string $message): void;

    public function flashValidationErrors(string $field, array $messages): void;

    public function hasFieldValidationErrors(string $field): bool;

    public function getFieldValidationErrors(string $field): array;

    public function flashSuccess(string $message): void;

    public function flashSuccesses(array $messages): void;

    public function flashInfo(string $message): void;

    public function flashInfos(array $messages): void;

    public function flashWarning(string $message): void;

    public function flashWarnings(array $messages): void;

    public function flashDanger(string $message): void;

    public function flashDangers(array $messages): void;

    public function flashError(string $message): void;

    public function flashErrors(array $messages): void;

    public function appendMessage(string $type, string $message): void;

    public function appendMessages(string $type, array $messages): void;

    public function appendSuccess(string $message): void;

    public function appendSuccesses(array $messages): void;

    public function appendInfo(string $message): void;

    public function appendInfos(array $messages): void;

    public function appendWarning(string $message): void;

    public function appendWarnings(array $messages): void;

    public function appendDanger(string $message): void;

    public function appendDangers(array $messages): void;

    public function appendError(string $message): void;

    public function appendErrors(array $messages): void;
}
