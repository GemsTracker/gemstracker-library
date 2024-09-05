<?php

declare(strict_types=1);

namespace Gems\Tracker\Field;

use Gems\Agenda\Repository\AgendaStaffRepository;
use Gems\Agenda\Repository\LocationRepository;
use Gems\Event\TrackFieldDisplayValueEvent;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TrackFieldDisplayValueEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    )
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            TrackFieldDisplayValueEvent::class => [
                ['getDisplayValue', 20],
            ],
        ];
    }

    public function getDisplayValue(TrackFieldDisplayValueEvent $event): void
    {
        if ($event->rawValue === null) {
            $event->stopPropagation();
        }

        $event->displayValue = match($event->type) {
            'caretaker' => $this->getCaretakerName((int)$event->rawValue),
            'location' => $this->getLocationName((int)$event->rawValue),
            default => $event->displayValue,
        };

        // Do not stop propagation to allow overwrites of above display values
    }

    private function getCaretakerName(int $caretakerId): ?string
    {
        /**
         * @var AgendaStaffRepository $agendaStaffRepository
         */
        $agendaStaffRepository = $this->container->get(AgendaStaffRepository::class);
        return $agendaStaffRepository->getStaffNameFromId($caretakerId);
    }

    private function getLocationName(int $locationId): ?string
    {
        /**
         * @var LocationRepository $locationRepository
         */
        $locationRepository = $this->container->get(LocationRepository::class);
        return $locationRepository->getLocationName($locationId);
    }
}