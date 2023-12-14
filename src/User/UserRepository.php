<?php

namespace Gems\User;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class UserRepository
{
    private Adapter $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function getEmailFromUserId(int $userId): ?string
    {
        $sql = new Sql($this->db);
        $select = $sql->select('gems__staff');
        $select->where(['gsf_id_user' => $userId]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        if ($result->valid() && $data = $result->current()) {
            return $data['gsf_email'];
        }
        return null;
    }
}