<?php

namespace Gems\Db\Migration;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Source\Factory;

class DatabaseInfo
{
    public function __construct(
        protected readonly Adapter $adapter
    )
    {}

    public function tableExists(string $tableName): bool
    {
        $metaData = Factory::createSourceFromAdapter($this->adapter);
        $tables = $metaData->getTableNames();

        return in_array($tableName, $tables);
    }

    public function tableHasColumn(string $tableName, string $columnName): bool
    {
        $metaData = Factory::createSourceFromAdapter($this->adapter);
        $columns = $metaData->getColumnNames($tableName);

        return in_array($columnName, $columns);
    }

    public function tableHasForeignKey(string $tableName, string $referencingColumn, string $referencedTable, string $referencedColumn): bool
    {
        return is_string($this->getForeignKeyName($tableName, $referencingColumn, $referencedTable, $referencedColumn));
    }

    public function getForeignKeyName(string $tableName, string $referencingColumn, string $referencedTable, string $referencedColumn): string|null
    {
        $metaData = Factory::createSourceFromAdapter($this->adapter);
        $constraints = $metaData->getConstraints($tableName);
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
}