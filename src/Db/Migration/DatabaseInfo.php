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
}