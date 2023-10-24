<?php

namespace Gems\Db;

use Gems\Exception;
use Laminas\Db\Adapter\Driver\Mysqli\Mysqli;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Sql\Select;

/**
 * Variant of the ResultFetcher that does unbuffered queries. Use this if the
 * query to execute returns a result that does not fit into memory.
 */
class UnbufferedResultFetcher extends ResultFetcher
{
    /**
     * Perform an un unbuffered query.
     *
     * @param Select|string $select
     * @param array|null $params
     * @return \Laminas\Db\Adapter\Driver\StatementInterface|\Laminas\Db\ResultSet\ResultSet
     * @throws Exception if driver is not supported.
     */
    public function query(Select|string $select, ?array $params = null)
    {
        if ($this->getAdapter()->getDriver() instanceof Pdo) {
            return $this->pdo_query($select, $params);
        } elseif ($this->getAdapter()->getDriver() instanceof Mysqli) {
            return $this->mysqli_query($select, $params);
        }

        throw new Exception(sprintf('Unbuffered queries are not supported with this driver (%s).',get_class($this->getAdapter()->getDriver())));
    }

    /**
     * Perform an un unbuffered query with the PDO driver.
     *
     * @param Select|string $select
     * @param array|null $params
     * @return \Laminas\Db\Adapter\Driver\StatementInterface|\Laminas\Db\ResultSet\ResultSet
     */
    private function pdo_query(Select|string $select, ?array $params = null)
    {
        $resource = $this->db->getDriver()->getConnection()->getResource();
        $query_buffered = false;
        $connection_buffered = $resource->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);

        if ($query_buffered != $connection_buffered) {
            $this->db->getDriver()->getConnection()->getResource()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $query_buffered);
        }
        $result = parent::query($select, $params);
        if ($query_buffered != $connection_buffered) {
            $this->db->getDriver()->getConnection()->getResource()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $connection_buffered);
        }

        return $result;
    }

    /**
     * Perform an un unbuffered query with the Mysqli driver.
     *
     * @param Select|string $select
     * @param array|null $params
     * @return \Laminas\Db\Adapter\Driver\StatementInterface|\Laminas\Db\ResultSet\ResultSet
     */
    private function mysqli_query(Select|string $select, ?array $params = null)
    {
        if (is_null($params)) {
            $params = [MYSQLI_USE_RESULT];
        } else  {
            $params[] = MYSQLI_USE_RESULT;
        }
        return parent::query($select, $params);
    }
}