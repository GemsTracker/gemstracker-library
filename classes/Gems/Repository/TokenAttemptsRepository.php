<?php

namespace Gems\Repository;

use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;
use Exception;

class TokenAttemptsRepository
{
    protected Adapter $adapter;

    public function __construct(protected ResultFetcher $resultFetcher, protected array $config)
    {
        $this->adapter = $this->resultFetcher->getAdapter();
    }

    public function markAttemptsAsBlocked(): void
    {
        $throttleSettings = $this->getAskThrottleSettings();

        $sql = new Sql($this->adapter);
        $update = $sql->update('gems__user_login_attempts');
        $update->set(['gta_activated' => 1]);
        $update->where->greaterThan('gta_datetime', new Expression(sprintf('DATE_SUB(NOW(), INTERVAL %d second)', (int)$throttleSettings['period'])));
        $sql->prepareStatementForSqlObject($update)->execute();
    }

    public function checkBlock(): bool
    {
        $throttleSettings = $this->getAskThrottleSettings();

        $select = $this->resultFetcher->getSelect('gems__token_attempts');
        $select->columns([
            new Expression('COUNT(*) AS attempts'),
            new Expression('UNIX_TIMESTAMP(MAX(gta_datetime)) - UNIX_TIMESTAMP() AS last'),
        ])
            ->where->greaterThan('gta_datetime', new Expression(sprintf('DATE_SUB(NOW(), INTERVAL %d second)', (int)$throttleSettings['period'])));
        $attemptData = $this->resultFetcher->fetchRow($select);

        $remainingDelay = ($attemptData['last'] + $throttleSettings['delay']);

        if ($attemptData['attempts'] > $throttleSettings['maxAttempts'] && $remainingDelay > 0) {
            return true;
        }
        return false;
    }

    public function addAttempt(string $tokenId, string $clientIp): void
    {
        $data = [
            'gta_id_token'   => $tokenId,
            'gta_ip_address' => $clientIp,
        ];

        $sql = new Sql($this->adapter);
        $insert = $sql->insert('gems__token_attempts')->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute();
    }

    public function getAskThrottleSettings(): array
    {
        if (isset($this->config['survey']['ask'], $this->config['survey']['ask']['askThrottle'])) {
            return $this->config['survey']['ask']['askThrottle'];
        }
        throw new Exception('No survey ask throttle settings');
    }

    public function prune(): void
    {
        $throttleSettings = $this->getAskThrottleSettings();

        $periodInSeconds = (int)$throttleSettings['period'] * 20;

        $sql = new Sql($this->adapter);
        $delete = $sql->delete('gems__token_attempts');
        $delete->where
            ->equalTo('gta_activated', 0)
            ->lessThan('gta_datetime', new Expression('DATE_SUB(NOW(), INTERVAL ' . $periodInSeconds . ' second)'));
        $sql->prepareStatementForSqlObject($delete)->execute();
    }
}