<?php

namespace Gems\Communication;

use Gems\Event\Application\RespondentCommunicationInterface;
use Gems\Event\Application\RespondentCommunicationSent;
use Gems\Event\Application\RespondentMailSent;
use Gems\Event\Application\TokenEventCommunicationSent;
use Gems\Event\Application\TokenEventInterface;
use Gems\Event\Application\TokenEventMailSent;
use Gems\Event\Application\TokenMarkedAsSent;
use Gems\Repository\CommJobRepository;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class EventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected ContainerInterface $container,
    )
    {}

    public static function getSubscribedEvents()
    {
        return [
            TokenEventCommunicationSent::NAME => [
                ['logRespondentCommunication'],
                ['updateToken'],
                ['removeFromJobQueueList'],
            ],
            RespondentCommunicationSent::NAME => [
                ['logRespondentCommunication']
            ],
            TokenEventMailSent::NAME => [
                ['logRespondentCommunication'],
                ['updateToken'],
                ['removeFromJobQueueList'],
            ],
            RespondentMailSent::NAME => [
                ['logRespondentCommunication']
            ],
            TokenMarkedAsSent::NAME => [
                ['updateToken'],
                ['removeFromJobQueueList'],
            ]
        ];
    }

    public function logRespondentCommunication(RespondentCommunicationInterface $event): void
    {
        $respondent = $event->getRespondent();
        $job = $event->getCommunicationJob();
        $currentUserId = $event->getCurrentUserId();

        $logData['grco_organization'] = $respondent->getOrganizationId();
        $logData['grco_id_to']        = $respondent->getId();
        if ($event instanceof TokenEventInterface) {
            $token = $event->getToken();
            $logData['grco_id_token']     = $token->getTokenId();
        }
        $subject = null;
        $from = [];
        $to = [];
        if ($event instanceof RespondentMailSent) {
            $email = $event->getEmail();
            $subject = $email->getSubject();
            $from = $email->getFrom();
            $to = $email->getTo();
        } elseif ($event instanceof RespondentCommunicationSent) {
            $subject = $event->getSubject();
            $from = $event->getFrom();
            $to = $event->getTo();
        }

        $logData['grco_id_by']        = $currentUserId;
        $logData['grco_method']       = 'unknown';
        if (isset($job['gcm_type'])) {
            $logData['grco_method']   = $job['gcm_type'];
        }
        $logData['grco_topic']        = substr($subject, 0, 120);

        $toAddressArray = array_map(function($from) {
            return $from->getName() . '<'.$from->getAddress().'>';
        }, $to);

        $fromAddressArray = array_map(function($from) {
           return $from->getName() . '<'.$from->getAddress().'>';
        }, $from);

        $logData['grco_address']      = substr(join(',', $toAddressArray), 0, 120);
        $logData['grco_sender']       = substr(join(',', $fromAddressArray), 0, 120);

        if (isset($job['gcj_id_message'])) {
            $logData['grco_id_message']   = $job['gcj_id_message'];
        }

        if (isset($job['gcj_id_job'])) {
            $logData['grco_id_job']   = $job['gcj_id_job'];
        }

        $changeDate                   = new Expression('NOW()');
        $logData['grco_changed']      = $changeDate;
        $logData['grco_changed_by']   = $currentUserId;
        $logData['grco_created']      = $changeDate;
        $logData['grco_created_by']   = $currentUserId;

        $db = $this->container->get(Adapter::class);
        $table = new TableGateway('gems__log_respondent_communications', $db);
        $table->insert($logData);
    }

    public function updateToken(TokenEventInterface $event): void
    {
        $token = $event->getToken();
        $token->setMessageSent();
    }

    public function removeFromJobQueueList(TokenEventInterface $event): void
    {
        $token = $event->getToken();
        $commJobRepository = $this->container->get(CommJobRepository::class);
        $commJobRepository->setTokenIsDoneInQueue($token->getTokenId());
    }
}