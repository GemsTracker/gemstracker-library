<?php

namespace Gems\Db\LegacyDbAdapter;

use PDO;

class PdoPgsqlAdapter extends \Zend_Db_Adapter_Pdo_Pgsql
{
    public function setConnection(PDO $connection)
    {
        $this->_connection = $connection;
    }
}