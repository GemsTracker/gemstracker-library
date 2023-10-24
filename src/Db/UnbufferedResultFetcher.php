<?php

namespace Gems\Db;

use Gems\Exception;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Sql\Select;

/**
 * Variant of the ResultFetcher that does unbuffered queries. Use this if the
 * query to execute returns a result that does not fit into memory.
 */
class UnbufferedResultFetcher extends ResultFetcher
{
    public function query(Select|string $select, ?array $params = null)
    {
        if (!$this->getAdapter()->getDriver() instanceof Pdo) {
            throw new Exception('Unbuffered queries are only supported with Pdo driver.');
        }
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
}