<?php

namespace Gems\Db\LegacyDbAdapter;

use PDO;

class PdoSqlsrvAdapter extends \Zend_Db_Adapter_Pdo_Sqlsrv
{
    public function setConnection(PDO $connection)
    {
        $this->_connection = $connection;
    }
}