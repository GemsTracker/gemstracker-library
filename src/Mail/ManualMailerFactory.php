<?php

namespace Gems\Mail;

use Gems\Db\ResultFetcher;
use Gems\Helper\Env;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Sql;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Messenger\MessageBusInterface;

class ManualMailerFactory
{
    protected array $config = [];

    protected string $defaultDsn = 'native://default';

    public function __construct(
        protected ResultFetcher $resultFetcher,
        protected EventDispatcherInterface $eventDispatcher,
        protected MessageBusInterface $messageBus,
        MailBouncer $mailBouncer,
        array $config)
    {
        if (isset($config['mail'])) {
            $this->config = $config['mail'];
        }
    }

    public function getMailer(?string $from): Mailer
    {
        $dsn = $this->getMailDsnFromFrom($from);

        $transport = Transport::fromDsn($dsn, $this->eventDispatcher);
        return new Mailer($transport, $this->messageBus, $this->eventDispatcher);
    }

    protected function getMailDsnFromFrom(?string $from): string
    {
        $serverInfo = $this->getMailServerInfo($from);
        if ($serverInfo) {
            $dsn = sprintf('smtp://%s:%s', $serverInfo['gms_server'], $serverInfo['gms_port']);
            if (isset($serverInfo['gms_user'], $serverInfo['gsm_password'])) {
                $dsn = sprintf('smtp://%s:%s@%s:%s', $serverInfo['gms_user'], $serverInfo['gms_password'], $serverInfo['gms_server'], $serverInfo['gms_port']);
            }
            return $dsn;
        }

        return Env::get('MAILER_DSN', $this->config['dsn'] ?? $this->defaultDsn);
    }

    protected function getMailServerInfo(?string $from): ?array
    {
        $select = $this->resultFetcher->getSelect('gems__mail_servers');
        $select
            ->columns(['gms_server', 'gms_port', 'gms_user', 'gms_password'])
            ->where(new Like(new Expression("'$from'"), 'gms_from'))
            ->order(new Expression('LENGTH(gms_from) DESC'))
            ->limit(1);

        return $this->resultFetcher->fetchRow($select);
    }
}
