<?php

/*
 * Fixes issues because of lacking date field in sqlite
 * 
 * To use implement a getDateFields method in a TEST version of the model
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
trait UnitTestDBFixTrait {
    
    protected function _loadTableMetaData(\Zend_Db_Table_Abstract $table, $alias = null) {
        // Just the regular
        parent::_loadTableMetaData($table, $alias);
        
        // Now fix the fields we need to have date and/or time        
        foreach ($this->getDateFields() as $name => $type) {
            switch ($type) {
                case 'date':
                    $finfo['type']          = \MUtil\Model::TYPE_DATE;
                    $finfo['storageFormat'] = 'yyyy-MM-dd';
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    $this->setOnLoad($name, array($this, 'formatLoadDate'));
                    break;

                case 'datetime':
                case 'timestamp':
                    $finfo['type']          = \MUtil\Model::TYPE_DATETIME;
                    $finfo['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    $this->setOnLoad($name, array($this, 'formatLoadDate'));
                    break;

                case 'time':
                    $finfo['type']          = \MUtil\Model::TYPE_TIME;
                    $finfo['storageFormat'] = 'HH:mm:ss';
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    $this->setOnLoad($name, array($this, 'formatLoadDate'));
                    break;
            }
            $this->set($name, $finfo);
        }
        $this->resetOrder();
    }
}
