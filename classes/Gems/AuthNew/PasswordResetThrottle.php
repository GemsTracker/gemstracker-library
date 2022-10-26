<?php

namespace Gems\AuthNew;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Sql;

class PasswordResetThrottle
{
    private readonly int $maxAttempts;
    private readonly int $timeframeSeconds;

    public function __construct(
        array $config,
        private readonly Adapter $db,
        private readonly string $ipAddress,
        private readonly int $organizationId,
    ) {
        $this->maxAttempts = $config['passwordResetThrottle']['maxAttempts'] ?? 6;
        $this->timeframeSeconds = $config['passwordResetThrottle']['timeframeSeconds'] ?? 600;
    }

    /**
     * Check whether the ip is blocked
     * @return int Number of minutes to wait for next attempt; or 0 if not applicable
     */
    public function checkBlock(): int
    {
        $sql = new Sql($this->db);
        $delete = $sql
            ->delete('gems__password_reset_attempts')
            ->where([
                new Operator('gpra_attempt_at', Operator::OP_LT, date('Y-m-d H:i:s', time() - $this->timeframeSeconds))
            ]);
        $sql->prepareStatementForSqlObject($delete)->execute();

        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns([
                'attempts' => new Expression('COUNT(*)'),
                'age' => new Expression('UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MIN(gpra_attempt_at))'),
            ])
            ->from('gems__password_reset_attempts')
            ->where([
                'gpra_ip_address' => $this->ipAddress,
                'gpra_id_organization' => $this->organizationId,
            ]);

        $resultSet = $sql->prepareStatementForSqlObject($select)->execute();
        $resultSet->buffer();
        $row = $resultSet->current();

        $minutes = 0;
        if ($row['attempts'] >= $this->maxAttempts) {
            $seconds = $this->timeframeSeconds - $row['age'];

            if ($seconds > 0) {
                $minutes = floor($seconds / 60) + 1;
            }
        }

        return $minutes;
    }

    /**
     * Register an attempt
     */
    public function registerAttempt(): void
    {
        $sql = new Sql($this->db);
        $insert = $sql->insert('gems__password_reset_attempts')->values([
            'gpra_id_organization' => $this->organizationId,
            'gpra_ip_address' => $this->ipAddress,
            'gpra_attempt_at' => new Expression('CURRENT_TIMESTAMP()'),
        ]);
        $sql->prepareStatementForSqlObject($insert)->execute();
    }
}
