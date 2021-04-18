<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use MUtil\Translate\TranslateableTrait;

/**
 * Extension of MUtil model with auto update changed and create fields.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_JoinModel extends \MUtil_Model_JoinModel
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
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
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
     * Add a table to the model with a left join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems_Model_JoinModel
     */
    public function addLeftTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addLeftTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
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
     * @return \Gems_Model_JoinModel
     */
    public function addRightTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addRightTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
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
     * @return \Gems_Model_JoinModel
     */
    public function addTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }
        return $this;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        if (! $this->translateAdapter) {
            $this->initTranslateable();
        }
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }

    /**
     *
     * @param string $table_name  Does not test for existence
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @return \Gems_Model_JoinModel
     */
    public function setTableSaveable($table_name, $fieldPrefix = null, $saveable = null)
    {
        parent::setTableSaveable($table_name, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }
}