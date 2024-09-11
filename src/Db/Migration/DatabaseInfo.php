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
}