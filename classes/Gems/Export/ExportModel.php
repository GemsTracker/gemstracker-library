<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export_ExportModel extends \MUtil_Model_ModelAbstract
{

    public function __construct()
    {
        parent::__construct('export');
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    protected function _load(array $filter, array $sort)
    {
        $result = array();
        foreach ($this->getItemsOrdered() as $item) {
            $result[0][$item] = $item;
        }
        return $result;
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    protected function _save(array $newValues, array $filter = null)
    {
        return $newValues;
    }

    public function delete($filter = true)
    {
        return false;
    }

    public function hasNew()
    {
        return false;
    }
}