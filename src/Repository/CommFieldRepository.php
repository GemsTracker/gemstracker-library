<?php

namespace Gems\Repository;

use Gems\Event\CommFieldTypeEvent;
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
}