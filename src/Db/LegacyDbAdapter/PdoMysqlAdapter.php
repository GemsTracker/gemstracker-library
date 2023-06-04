<?php

namespace Gems\Db\LegacyDbAdapter;

use PDO;

class PdoMysqlAdapter extends \Zend_Db_Adapter_Pdo_Mysql
{
    public function setConnection(PDO $connection)
    {
        $this->_connection = $connection;
    }
}