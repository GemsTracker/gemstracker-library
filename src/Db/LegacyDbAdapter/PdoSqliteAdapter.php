<?php

namespace Gems\Db\LegacyDbAdapter;

use PDO;

class PdoSqliteAdapter extends \Zend_Db_Adapter_Pdo_Sqlite
{
    public function setConnection(PDO $connection)
    {
        $this->_connection = $connection;
    }
}