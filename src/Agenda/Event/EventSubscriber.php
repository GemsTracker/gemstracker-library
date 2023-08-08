<?php

namespace Gems\Agenda\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentChangedEvent::class => [
                ['updateTracksForAppointment'],
                ['updateAppointmentInfo'],
            ],
        ];
    }

    public function updateAppointmentInfo(AppointmentChangedEvent $event)
    {
        $agenda = $event->getAgenda();
        $appointment = $event->getAppointment();
        $agenda->updateAppointmentInfo($appointment);
    }

    public function updateTracksForAppointment(AppointmentChangedEvent $event)
    {
        $agenda = $event->getAgenda();
        $appointment = $event->getAppointment();
        $tokensChanged = $agenda->updateTracksForAppointment($appointment);
        $event->setTokensChanged($tokensChanged);
    }
}