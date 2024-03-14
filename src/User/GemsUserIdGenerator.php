<?php

namespace Gems\User;

use DateTimeImmutable;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Sql\Sql;

class GemsUserIdGenerator
{
    /**
     * The length of a user id.
     *
     * @var int
     */
    protected int $userIdLen = 8;

    public function __construct(
        protected readonly Adapter $db)
    {
    }

    /**
     * Create a \Gems project wide unique user id
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string|null $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return int
     */
    public function createGemsUserId(mixed $value, bool $isNew = false, ?string $name = null, array $context = []): int
    {
        if ($value) {
            return (int) $value;
        }

        $now = new DateTimeImmutable();
        $sql = new Sql($this->db);

        do {
            $out = mt_rand(1, 9);
            for ($i = 1; $i < $this->userIdLen; $i++) {
                $out .= mt_rand(0, 9);
            }
            // Make it a number
            $out = intval($out);

            $values = [
                'gui_id_user' => $out,
                'gui_created' => $now->format('Y-m-d H:i:s'),
            ];
            $insert = $sql->insert('gems__user_ids')->values($values);
            try {
                $result = $sql->prepareStatementForSqlObject($insert)->execute();
                if ((!$result instanceof ResultInterface) || 0 === $result->getAffectedRows()) {
                    $out = null;
                }
            } catch(InvalidQueryException $e) {
                $out = null;
            }

        } while (null === $out);

        return $out;
    }
}