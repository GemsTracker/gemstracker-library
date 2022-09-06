<?php

namespace Gems\AuthNew;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\IsNotNull;
use Laminas\Db\Sql\Sql;

class LoginThrottle
{
    private readonly int $failureBlockCount;
    private readonly int $failureIgnoreTime;

    public function __construct(
        array $config,
        private readonly Adapter $db,
        private readonly string $loginName,
        private readonly int $organizationId,
    ) {
        $this->failureBlockCount = $config['loginThrottle']['failureBlockCount'] ?? 6;
        $this->failureIgnoreTime = $config['loginThrottle']['failureIgnoreTime'] ?? 600;
    }

    /**
     * Check whether the user is blocked
     * @return int Number of minutes to wait for next attempt; or 0 if not applicable
     */
    public function checkBlock(): int
    {
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns([
                'wait' => new Expression('UNIX_TIMESTAMP(gula_block_until) - UNIX_TIMESTAMP()')
            ])
            ->from('gems__user_login_attempts')
            ->where([
                new IsNotNull('gula_block_until'),
                'gula_login' => $this->loginName,
                'gula_id_organization' => $this->organizationId,
            ])
            ->limit(1);

        $resultSet = $sql->prepareStatementForSqlObject($select)->execute();
        $resultSet->buffer();
        $row = $resultSet->current();

        $minutes = 0;
        if ($row) {
            $seconds = $row['wait'];

            if ($seconds > 0) {
                $minutes = floor($seconds / 60) + 1;
            } else {
                $update = $sql->update('gems__user_login_attempts')
                    ->where([
                        'gula_login' => $this->loginName,
                        'gula_id_organization' => $this->organizationId,
                    ])
                    ->set([
                        'gula_failed_logins' => 0,
                        'gula_last_failed' => null,
                        'gula_block_until' => null,
                    ]);

                $sql->prepareStatementForSqlObject($update)->execute();
            }
        }

        return $minutes;
    }

    /**
     * Update block status according to authentication result
     * @param AuthenticationResult $result
     * @return int Number of minutes to wait for next attempt; or 0 if not applicable
     */
    public function processAuthenticationResult(AuthenticationResult $result): int
    {
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns([
                'gula_failed_logins',
                'gula_last_failed',
                'since_last' => new Expression('UNIX_TIMESTAMP() - UNIX_TIMESTAMP(gula_last_failed)')
            ])
            ->from('gems__user_login_attempts')
            ->where([
                'gula_login' => $this->loginName,
                'gula_id_organization' => $this->organizationId,
            ])
            ->limit(1);

        $resultSet = $sql->prepareStatementForSqlObject($select)->execute();
        $resultSet->buffer();

        $values = $resultSet->current();

        if ($values === null) {
            // The first login attempt
            $values = [
                'gula_login'           => $this->loginName,
                'gula_id_organization' => $this->organizationId,
                'gula_failed_logins'   => 0,
                'gula_last_failed'     => null,
                'gula_block_until'     => null,
            ];
            $sinceLast = 0;
        } else {
            $sinceLast = $values['since_last'];
            unset($values['since_last']);
        }

        $minutes = 0;
        if ($result->isValid()) {
            // Reset login failures
            $values['gula_failed_logins']   = 0;
            $values['gula_last_failed']     = null;
            $values['gula_block_until']     = null;
        } else {
            if ($sinceLast > $this->failureIgnoreTime) {
                // Reset the counters when the last login was longer ago than the delay factor
                $values['gula_failed_logins'] = 1;
            } elseif ($result->getCode() !== AuthenticationResult::DISALLOWED_IP) {
                // Increment failed login
                $values['gula_failed_logins'] += 1;
            }

            // Set the block when needed
            if ($values['gula_failed_logins'] >= $this->failureBlockCount) {
                $values['gula_block_until'] = new Expression('DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ' . $this->failureIgnoreTime . ' SECOND)');
                $minutes = floor($this->failureIgnoreTime / 60) + 1;
            }

            // Always record the last fail
            $values['gula_last_failed'] = new Expression('CURRENT_TIMESTAMP');
        }

        if (array_key_exists('gula_login', $values)) {
            $insert = $sql->insert('gems__user_login_attempts')->values($values, Insert::VALUES_MERGE);
            $sql->prepareStatementForSqlObject($insert)->execute();
        } else {
            $update = $sql->update('gems__user_login_attempts')
                ->where([
                    'gula_login' => $this->loginName,
                    'gula_id_organization' => $this->organizationId,
                ])
                ->set($values);
            $sql->prepareStatementForSqlObject($update)->execute();
        }

        return $minutes;
    }
}
