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
        return in_array($tableName, $metaData->getTableNames());

    }

    public function tableHasColumn(string $tableName, string $columnName): bool
    {
        $metaData = Factory::createSourceFromAdapter($this->adapter);
        $columns = $metaData->getColumnNames($tableName);

        return in_array($columns, $columns);
    }
}