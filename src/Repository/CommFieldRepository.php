<?php

namespace Gems\Repository;

use Gems\Event\CommFieldTypeEvent;
use Gems\Event\RawCommFieldEvent;
use Gems\Mail\ProjectMailFields;
use Gems\Mail\RespondentMailFields;
use Gems\Mail\TokenMailFields;
use Gems\Mail\UserMailFields;
use Psr\EventDispatcher\EventDispatcherInterface;

class CommFieldRepository
{
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public function getCommFieldTypes(): array
    {
        $commFieldTypes = [
            'staff' => 'Staff',
            'respondent' => 'Respondent',
            'token' => 'Token',
            'staffPassword' => 'Password reset',
        ];

        $commFieldTypeEvent = new CommFieldTypeEvent($commFieldTypes);

        $this->eventDispatcher->dispatch($commFieldTypeEvent);

        return $commFieldTypeEvent->getFieldTypes();
    }

    public function getRawCommFields(string $type): array
    {
        $rawFields = match($type) {
            'token' => TokenMailFields::getRawFields(),
            'respondent' => RespondentMailFields::getRawFields(),
            'staff' => UserMailFields::getRawFields(),
            'staffPassword' => UserMailFields::getRawFields(),
            'project' => ProjectMailFields::getRawFields(),
            default => null,
        };

        if ($rawFields) {
            return $rawFields;
        }

        $rawFieldEvent = new RawCommFieldEvent($type);
        $this->eventDispatcher->dispatch($rawFieldEvent);

        return $rawFieldEvent->rawFields;
    }
}