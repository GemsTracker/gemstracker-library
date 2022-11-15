<?php

namespace Gems\Db;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;

class ResultFetcher
{
    protected Sql $sql;

    public function __construct(protected Adapter $db)
    {
        $this->sql = new Sql($db);
    }

    public function fetchPairs(Select|string $select, ?array $params = null)
    {
        $resultArray = $this->fetchAllAssociative($select, $params);
        if (count($resultArray) === 0) {
            return null;
        }
        $firstRow = reset($resultArray);

        $keyKey = key($firstRow);
        $valueKey = key(array_slice($firstRow, 1, 1, true));

        return array_column($resultArray, $valueKey, $keyKey);
    }

    public function fetchAll(Select|string $select, ?array $params = null): array
    {
        return $this->fetchAllAssociative($select, $params);
    }

    public function fetchOne(Select|string $select, ?array $params = null): array
    {
        $result = $this->query($select, $params);
        $row = $result->current();
        return reset($row);
    }

    public function fetchRow(Select|string $select, ?array $params = null): array
    {
        return $this->fetchAssociative($select, $params);
    }

    public function fetchAssociative(Select|string $select, ?array $params = null): array
    {
        $result = $this->query($select, $params);
        return $result->current();
    }

    public function fetchAllAssociative(Select|string $select, ?array $params = null): array
    {
        $result = $this->query($select, $params);
        return $result->toArray();
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

        return $this->db->query($select, $params, $resultSet);
    }

    public function getSelect(null|string|TableIdentifier $table = null)
    {
        return $this->sql->select($table);
    }
}