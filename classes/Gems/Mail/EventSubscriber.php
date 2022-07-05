<?php

namespace Gems\Mail;

use Gems\Event\Application\RespondentMailSent;
use Gems\Event\Application\TokenMailSent;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    private Adapter $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public static function getSubscribedEvents()
    {
        return [
            TokenMailSent::NAME => [
                ['logRespondentMail'],
                ['updateToken'],
            ],
            RespondentMailSent::NAME => [
                ['logRespondentMail']
            ],
        ];
    }

    public function logRespondentMail(RespondentMailSent $event)
    {
        $email = $event->getEmail();
        $respondent = $event->getRespondent();
        $job = $event->getMailJob();
        $currentUser = $event->getCurrentUser();

        $logData['grco_organization'] = $respondent->getOrganizationId();
        $logData['grco_id_to']        = $respondent->getId();
        if ($event instanceof TokenMailSent) {
            $token = $event->getToken();
            $logData['grco_id_token']     = $token->getTokenId();
        }

        $logData['grco_id_by']        = $currentUser->getUserId();
        $logData['grco_method']       = 'email';
        $logData['grco_topic']        = substr($email->getSubject(), 0, 120);

        $toAddressArray = array_map(function($from) {
            return $from->getName() . '<'.$from->getAddress().'>';
        }, $email->getTo());

        $fromAddressArray = array_map(function($from) {
           return $from->getName() . '<'.$from->getAddress().'>';
        }, $email->getFrom());

        $logData['grco_address']      = substr(join(',', $toAddressArray), 0, 120);
        $logData['grco_sender']       = substr(join(',', $fromAddressArray), 0, 120);

        if (isset($job['gcj_id_message'])) {
            $logData['grco_id_message']   = $job['gcj_id_message'];
        }

        if (isset($job['gcj_id_job'])) {
            $logData['grco_id_job']   = $job['gcj_id_job'];
        }

        $changeDate                   = new \MUtil_Db_Expr_CurrentTimestamp();
        $logData['grco_changed']      = $changeDate;
        $logData['grco_changed_by']   = $currentUser->getUserId();
        $logData['grco_created']      = $changeDate;
        $logData['grco_created_by']   = $currentUser->getUserId();

        $table = new TableGateway('gems__log_respondent_communications', $this->db);
        $table->insert($logData);
    }

    public function updateToken(TokenMailSent $event): void
    {
        $token = $event->getToken();
        $token->setMessageSent();
    }
}