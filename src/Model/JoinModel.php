<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use MUtil\Translate\TranslateableTrait;

/**
 * Extension of \MUtil model with auto update changed and create fields.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class JoinModel extends \MUtil\Model\JoinModel
{
    use TranslateableTrait;

    /**
     * Create a model that joins two or more tables
     *
     * @param string $name        A name for the model
     * @param string $startTable  The base table for the model
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     */
    public function __construct($name, $startTable, $fieldPrefix = null, $saveable = null)
    {
        parent::__construct($name, $startTable, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems\Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }
    }

    /**
     *
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @return mixed The saveable setting to use
     */
    protected function _checkSaveable($saveable, $fieldPrefix)
    {
        if (null === $saveable) {
            return $fieldPrefix ? parent::SAVE_MODE_ALL : null;
        }

        return $saveable;
    }

    /**
     * @param array $tables the array of tables in the filter staaatement
     * @param string $expression
     */
    protected function _findExpressionTables(array &$tables, $expression)
    {
        $matches = [];
        preg_match_all('/([a-z]\w+\.[a-z][1-2a-z]+_\w+|[a-z][1-2a-z]+_\w+)/i', $expression, $matches);
        // \MUtil\EchoOut\EchoOut::track($expression, array_unique($matches[0]));
        
        foreach (array_unique($matches[0]) as $field) {
            switch (strtoupper($field)) {
                case 'CONCAT_WS':
                case 'CHAR_LENGTH':
                case 'CURRENT_DATE':
                case 'CURRENT_TIME':
                case 'CURRENT_TIMESTAMP':
                case 'DATE_ADD':
                case 'DATE_FORMAT':
                case 'DATE_SUB':
                case 'STR_TO_DATE':
                case 'TIME_FORMAT':
                    // do nothing for common functions
                    break;
                    
                default:
                    $this->_findFieldTable($tables, $field);
            }
        }
    }

    /**
     * @param array $tables the array of tables in the filter staaatement
     * @param string $field
     */
    protected function _findFieldTable(array &$tables, $field)
    {
        // Check for tablename.fieldname, unless the field name is an aliased table
        if ((! $this->has($field)) && \MUtil\StringUtil\StringUtil::contains($field, '.')) {
            list($table, $newField) = explode('.', $field, 2);
            
            if ($table == $this->get($newField, 'table')) {
                $field = $newField;
            }
        }
        $table = $this->get($field, 'table');
        if ($table && (! isset($tables[$table]))) {
            // Lookup any extra tables required for this join
            if (isset($this->_joinTables[$table])) {
                foreach ($this->_joinTables[$table] as $from => $to) {
                    if (! is_int($from)) {
                        $this->_findFieldTable($tables, $from);
                    }
                }
            }
            
            $tables[$table] = $table;
        }
    }

    /**
     * @param array $tables the array of tables in the filter staaatement
     * @param array $filter
     */
    protected function _findFilterTables(array &$tables, array $filter)
    {
        foreach ($filter as $key => $value) {
            foreach ($filter as $name => $value) {
                if (is_int($name)) {
                    if (is_array($value)) {
                        $this->_findFilterTables($tables, $value);
                    } else {
                        $this->_findExpressionTables($tables, $value);
                    }
                } elseif ($this->has($name)) {
                    if ($expression = $this->get($name, 'column_expression')) {
                        $this->_findExpressionTables($tables, $value);
                    } else {
                        $this->_findFieldTable($tables, $name);
                    }
                }
            }
        }
    }

    /**
     * Add a table to the model with a left join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems\Model\JoinModel
     * @no-named-arguments
     */
    public function addLeftTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addLeftTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems\Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }

    /**
     * Add a table to the model with a right join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems\Model\JoinModel
     * @no-named-arguments
     */
    public function addRightTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addRightTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems\Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }

    /**
     * Add a table to the model with an inner join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems\Model\JoinModel
     * @no-named-arguments
     */
    public function addTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems\Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }
        return $this;
    }

    /**
     * Get a minimized select statement with a less complicated join using only the fields in the filter  
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $cols Optional fields to return in the select
     * @return \Zend_Db_Select
     */
    public function getFilteredSelect(array $filter, array $cols = [])
    {
        if (! $cols) {
            $cols = $this->getKeys();
        }
        // Remove selector fields
        unset($filter['limit'], $filter['page'], $filter['items']);
        
        $filter = $this->_checkFilterUsed($filter);
        
        $baseSelect = $this->getSelect();
        
        $usedTables = [];
        foreach ($cols as $alias => $field) {
            $this->_findFieldTable($usedTables, $field);
        }
        $this->_findFilterTables($usedTables, $filter);

        $froms        = $baseSelect->getPart(\Zend_Db_Select::FROM);
        $from         = key($froms);
        $adapter      = $this->getAdapter();
        $outputSelect = $adapter->select();

        $outputSelect->from($from, $cols);
        // \MUtil\EchoOut\EchoOut::track($usedTables, $froms, $filter);

        // Join the used tables
        foreach ($usedTables as $table) {
            if (isset($froms[$table])) {
                if ($table == $froms[$table]['tableName']) {
                    $joinTable = $table;
                } else {
                    $joinTable = [$table => $froms[$table]['tableName']];
                }
                switch ($froms[$table]['joinType']) {
                    case \Zend_Db_Select::INNER_JOIN:
                        $outputSelect->joinInner($joinTable, $froms[$table]['joinCondition'], []);
                        break;

                    case \Zend_Db_Select::LEFT_JOIN:
                        $outputSelect->joinLeft($joinTable, $froms[$table]['joinCondition'], []);
                        break;
                        
                    case \Zend_Db_Select::RIGHT_JOIN:
                        $outputSelect->joinRight($joinTable, $froms[$table]['joinCondition'], []);
                        break;

                        // Other Join types can currently not be used in a JoinModel
                }
            }
        }

        $where = $this->_createWhere($filter, $adapter, true);
        if ($where) {
            $outputSelect->where($where);
        }

        return $outputSelect;
    }

    /**
     *
     * @param string $tableName  Does not test for existence
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @return self
     */
    public function setTableSaveable($tableName, $fieldPrefix = null, $saveable = null)
    {
        parent::setTableSaveable($tableName, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems\Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }
}