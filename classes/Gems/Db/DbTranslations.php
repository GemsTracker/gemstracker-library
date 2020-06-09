<?php


namespace Gems\Db;


class DbTranslations extends \MUtil_Registry_TargetAbstract
{
    use DbTranslateTrait;

    /**
     * Config for database translations
     * The to translate tables as keys with an array of all table columns with translations as value
     *
     * @var array|null
     */
    protected $config;

    public function __construct(array $config=null)
    {
        $this->config = $config;
    }

    public function translateRow($row)
    {
        $tablesWithTranslations = $this->getTablesWithTranslations();
        $translations = $this->getTranslations();
        if (!$translations) {
            return $row;
        }

        if ($this->config) {
            $translateTables = array_intersect(array_keys($tablesWithTranslations), array_keys($this->config));
            if (count($translateTables) === 0) {
                return $row;
            }

            foreach($translateTables as $translateTable) {
                if (is_array($this->config[$translateTable])) {
                    $fields = $this->config[$translateTable];
                } else {
                    $fields = array_intersect(array_keys($row), $tablesWithTranslations[$translateTable]);
                }

                $model = new \MUtil_Model_TableModel($translateTable);
                $tableKeys = $model->getKeys();
                $itemNames = $model->getItemNames();

                $keysExist = true;

                $keyValues = [];
                foreach($tableKeys as $tableKey) {
                    if (!isset($row[$tableKey])) {
                        $keysExist = false;
                        break;
                    }
                    $keyValues[] = $row[$tableKey];
                }

                // if keys are missing, we cannot translate these table values
                if (!$keysExist) {
                    continue;
                }

                foreach($fields as $field) {
                    $key = $this->getKey($translateTable, $field, $keyValues);

                    if (isset($translations[$key])) {
                        $row[$field] = $translations[$key];
                    }
                }
            }
        }

        return $row;
    }

    /**
     * Translate all rows according to either the config or the supplied rows
     * Table keys should be included in the result
     * @param array $rows list of database rows
     * @return array
     */
    public function translateRows(array $rows)
    {
        foreach($rows as $key=>$row) {
            $rows[$key] = $this->translateRow($row);
        }

        return $rows;
    }

    /**
     * Translate all rows from a \Zend_Db_Select
     * If no config is supplied, it will be generated from the select
     *
     * @param \Zend_Db_Select $select
     * @return array list of database rows already translated
     * @throws \Zend_Db_Select_Exception
     */
    public function translateRowsFromSelect(\Zend_Db_Select $select)
    {
        if ($this->config === null) {
            // Generate config from select;
            $this->config = [];
            $tables = array_keys($select->getPart('from'));
            $columnPart = $select->getPart('columns');
            $columns = [];
            foreach($columnPart as $column) {
                if (isset($column[0], $column[1])) {
                    $columns[$column[0]][] = $column[1];
                }
            }
            if (!empty($columns)) {
                $this->config = $columns;
            }
            foreach($tables as $table) {
                if (!isset($this->config[$table])) {
                    $this->config[$table] = true;
                }
            }
        }

        $rows = $this->db->fetchAll($select);

        return $this->translateRows($rows);
    }

    /**
     * Translate all rows from a \Zend_Db_Select and return the first two columns in the select
     * If no config is supplied, it will be generated from the select
     *
     * @param \Zend_Db_Select $select
     * @return array list of database rows already translated
     * @throws \Zend_Db_Select_Exception
     */
    public function translatePairsFromSelect(\Zend_Db_Select $select)
    {
        $columnPart = $select->getPart('columns');
        $columns = [];
        foreach($columnPart as $column) {
            if (isset($column[1])) {
                $columns[] = $column[1];
            }
        }

        if (count($columns) < 2) {
            return null;
        }

        $result = $this->translateRowsFromSelect($select);

        return array_column($result, $columns[1], $columns[0]);
    }

}
