<?php

namespace Gems\Db\Migration;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\MetadataInterface;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\Metadata\Source\Factory;

class DatabaseInfo
{
    protected readonly MetadataInterface $metaData;

    public function __construct(
        protected readonly Adapter $adapter
    )
    {
        $this->metaData = Factory::createSourceFromAdapter($this->adapter);
    }

    /**
     * Expose metaData for testing purposes only.
     */
    public function getMetaData(): MetadataInterface
    {
        return $this->metaData;
    }

    public function getForeignKeyName(string $tableName, string $referencingColumn, string $referencedTable, string $referencedColumn): string|null
    {
        $constraints = $this->metaData->getConstraints($tableName);
        foreach ($constraints as $constraint) {
            if ($constraint->isForeignKey() &&
                $constraint->getReferencedTableName() == $referencedTable &&
                in_array($referencingColumn, $constraint->getColumns()) &&
                in_array($referencedColumn, $constraint->getReferencedColumns())) {
                return $constraint->getName();
            }
        }

        return null;
    }

    public function tableExists(string $tableName): bool
    {
        $tables = $this->metaData->getTableNames();

        return in_array($tableName, $tables);
    }

    public function tableHasColumn(string $tableName, string $columnName): bool
    {
        $columns = $this->metaData->getColumnNames($tableName);

        return in_array($columnName, $columns);
    }

    public function tableHasConstraint(string $tableName, string $constraintOrColumnName): bool
    {
        foreach ($this->metaData->getConstraints($tableName) as $constraint) {
            if ($constraint instanceof ConstraintObject) {
                if ($constraint->getName() == $constraintOrColumnName) {
                    return true;
                }
                $cols = $constraint->getColumns();
                if ((1 === count($cols)) && ($constraintOrColumnName == reset($cols))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function tableHasForeignKey(string $tableName, string $referencingColumn, string $referencedTable, string $referencedColumn): bool
    {
        return is_string($this->getForeignKeyName($tableName, $referencingColumn, $referencedTable, $referencedColumn));
    }

    /**
     * Checks if an index with the given name exists on the table.
     */
    public function tableHasIndex(string $tableName, string $indexName): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?";
        $results = $this->adapter->query($sql, [$tableName, $indexName]);
        $row = $results->current();
        return isset($row['cnt']) && $row['cnt'] > 0;
    }

    /**
     * Checks if an index exists on the table for the given column(s).
     * @param string $tableName
     * @param array|string $columns
     * @return bool
     */
    public function tableHasIndexOnColumns(string $tableName, array|string $columns): bool
    {
        $columns = (array) $columns;
        $sql = "SELECT index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? GROUP BY index_name";
        $results = $this->adapter->query($sql, [$tableName]);
        $columnsStr = implode(',', $columns);
        foreach ($results as $row) {
            if ($row['cols'] === $columnsStr) {
                return true;
            }
        }
        return false;
    }
}
