<?php

namespace Gems\AuthNew;

use Gems\AccessLog\AccesslogRepository;
use Gems\AuthNew\Adapter\EmbedAuthenticationResult;
use Gems\AuthNew\Adapter\EmbedIdentity;
use Gems\AuthNew\Adapter\GemsTrackerAuthenticationResult;
use Gems\Event\Application\AuthenticatedEvent;
use Gems\Event\Application\AuthenticationFailedLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AccesslogRepository $accesslogRepository,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            AuthenticatedEvent::NAME => [
                ['authenticated'],
            ],
            AuthenticationFailedLoginEvent::NAME => [
                ['authenticationFailed'],
            ],
        ];
    }

    public function authenticated(AuthenticatedEvent $event): void
    {
        $result = $event->result;

        if ($result instanceof EmbedAuthenticationResult) {
            /** @var EmbedIdentity $identity */
            $identity = $result->getIdentity();
            $message = 'Successful embedded login ' . $identity->getSystemUserLoginName() . ' / ' . $identity->getLoginName();

            //$this->accesslogRepository->logChange(null, $message);
        }
    }

    public function authenticationFailed(AuthenticationFailedLoginEvent $event): void
    {
        $result = $event->result;

        if ($result instanceof EmbedAuthenticationResult) {
            $message = 'Failed embedded login [' . $result->getCode() . ']: ' . implode(';', $result->getMessages());

            //$this->accesslogRepository->logChange(null, $message);
        } elseif ($result instanceof GemsTrackerAuthenticationResult) {
            $message = 'Failed GT login [' . $result->getCode() . ']: ' . implode(';', $result->getMessages());

            //$this->accesslogRepository->logChange(null, $message);
        }
    }
}
