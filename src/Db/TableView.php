<?php
/**
 *
 * @package    Gems
 * @subpackage Db
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Db;

/**
 * Allows to use a view in the database contoller and view it's contents
 *
 * @package    Gems
 * @subpackage Db
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class TableView extends \Zend_Db_Table_Abstract {
    
    /**
     * For a view there is no primary key, because of this we can not use the nomal tablemodel
     */
    protected function _setupPrimaryKey()
    {
        // Made empty we dont care about the key
    }
    
    public function delete($where) {
        throw new \Gems\Exception('Deleting from this view is not allowed');
    }
    
    public function insert(array $data) {
        throw new \Gems\Exception('Inserting in this view is not allowed');
    }
    
    public function update(array $data, $where) {
        throw new \Gems\Exception('Updating records in this view is not allowed');
    }
}
