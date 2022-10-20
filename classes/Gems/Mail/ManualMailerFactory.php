<?php

namespace Gems\Mail;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Sql;
use Symfony\Component\Mailer\Transport;

class ManualMailerFactory
{
    protected array $config = [];

    protected Adapter $db;

    protected string $defaultDsn = 'native://default';

    public function __construct(Adapter $db, array $config)
    {
        if (isset($config['mail'])) {
            $this->config = $config['mail'];
        }
        $this->db = $db;
    }

    public function getMailer(?string $from): \Symfony\Component\Mailer\Mailer
    {
        $dsn = $this->getMailDsnFromFrom($from);

        $transport = Transport::fromDsn($dsn);
        return new \Symfony\Component\Mailer\Mailer($transport);
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

        if (isset($this->config['dsn'])) {
            return $this->config['dsn'];
        }

        return $this->defaultDsn;
    }

    protected function getMailServerInfo(?string $from): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('gems__mail_servers')
            ->columns(['gms_server', 'gms_port', 'gms_user', 'gms_password'])
            ->where(new Like(new Expression("'$from'"), 'gms_from'))
            ->order(new Expression('LENGTH(gms_from) DESC'))
            ->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            return $result->current();
        }

        return null;
    }
}
