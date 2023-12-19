<?php

namespace Gems\Db;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\SqlInterface;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\TableGateway;

class ResultFetcher
{
    protected Sql $sql;

    public function __construct(protected Adapter $db)
    {
        $this->sql = new Sql($db);
    }

    public function fetchPairs(Select|string $select, ?array $params = null): ?array
    {
        $resultArray = $this->fetchAllAssociative($select, $params);
        if (count($resultArray) === 0) {
            return [];
        }
        $firstRow = reset($resultArray);

        $keyKey   = key($firstRow);
        $valueKey = key(array_slice($firstRow, 1, 1, true));
        if (! $valueKey) {
            // For one column us it for both data sets
            $valueKey = $keyKey;
        }

        return array_column($resultArray, $valueKey, $keyKey);
    }

    public function deleteFromTable(string $tableName, mixed $where): int
    {
        $table = new TableGateway($tableName, $this->getAdapter());
        return $table->delete($where);
    }

    public function fetchAll(Select|string $select, ?array $params = null): ?array
    {
        return $this->fetchAllAssociative($select, $params);
    }

    public function fetchCol(Select|string $select, ?array $params = null): ?array
    {
        $resultArray = $this->fetchAllAssociative($select, $params);
        if (count($resultArray) === 0) {
            return null;
        }
        $firstRow = reset($resultArray);
        $valueKey = key($firstRow);

        return array_column($resultArray, $valueKey);
    }

    public function fetchOne(Select|string $select, ?array $params = null): string|int|null
    {
        $result = $this->query($select, $params);
        $row = $result->current();
        if (is_array($row)) {
            return reset($row);
        }
        return null;
    }

    public function fetchRow(Select|string $select, ?array $params = null): ?array
    {
        return $this->fetchAssociative($select, $params);
    }

    public function fetchAssociative(Select|string $select, ?array $params = null): ?array
    {
        $result = $this->query($select, $params);
        $row = $result->current();
        if (is_array($row)) {
            return $result->current();
        }
        return null;
    }

    public function fetchAllAssociative(Select|string $select, ?array $params = null): ?array
    {
        $result = $this->query($select, $params);
        return $result->toArray();
    }

    public function getAdapter(): Adapter
    {
        return $this->db;
    }

    public function getPlatform(): PlatformInterface
    {
        return $this->db->getPlatform();
    }

    public function getQueryString(SqlInterface $select): string
    {
        return $select->getSqlString($this->db->getPlatform());
    }

    public function getSelect(null|string|TableIdentifier $table = null): Select
    {
        return $this->sql->select($table);
    }

    public function query(Select|string $select, ?array $params = null)
    {
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        if ($select instanceof Select) {
            $statement = $this->sql->prepareStatementForSqlObject($select);
            $result = $statement->execute($params);
            $resultSet->initialize($result);
            return $resultSet;
        }

        if ($params === null) {
            $params = Adapter::QUERY_MODE_EXECUTE;
        }

        return $this->db->query($select, $params, $resultSet);
    }

    public function insertIntoTable(string $tableName, array $values): int
    {
        $table = new TableGateway($tableName, $this->getAdapter());
        $table->insert($values);
        return $this->getAdapter()->getDriver()->getLastGeneratedValue();
    }

    public function updateTable(string $tableName, array $values, mixed $where): int
    {
        $table = new TableGateway($tableName, $this->getAdapter());
        return $table->update($values, $where);
    }
}