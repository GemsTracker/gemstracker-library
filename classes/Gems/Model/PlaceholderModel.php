<?php
/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

/**
 * A placeholder array model
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2021 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.9,0
 */
class Gems_Model_PlaceholderModel extends \MUtil_Model_ArrayModelAbstract
{
    /**
     * @var array The data in the model
     */
	protected $data;

    /**
     * Gems_Model_PlaceholderModel constructor.
     *
     * @param string $modelName
     * @param array $fieldArray Nested array containing settings
     * @param array $data
     */
	public function __construct($modelName, array $fieldArray, array $data = array())
	{
        parent::__construct($modelName);
        
		$this->data = $data;

		if ($fieldArray) {
		    $this->setMulti($fieldArray);
        }
	}

    /**
     * Filters the data array using a model filter
     *
     * @param \Traversable $data
     * @param array $filters
     * @return \Traversable
     */
    protected function _filterData($data, array $filters)
    {
        $limit = false;
        if (isset($filters['limit'])) {
            $limit = $filters['limit'];
            unset($filters['limit']);
        }

        $filteredData = parent::_filterData($data, $filters);

        if ($limit) {
            if (is_array($limit)) {
                $filteredData = array_slice($filteredData, $limit[1], $limit[0]);
            } elseif (is_numeric($limit)) {
                $filteredData = array_slice($filteredData, 0, $limit);
            }
        }

        return $filteredData;
    }

    /**
     * Calculates the total number of items in a model result with certain filters
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return integer number of total items in model result
     * @throws Zend_Db_Select_Exception
     */
    public function getItemCount($filter = true, $sort = true)
    {
        $data = $this->_loadAllTraversable();

        if ($filter) {
            $data = $this->_filterData($data, $filter);
        }

        return count($data);
    }

	/**
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return \Traversable Return an iterator over or an array of all the rows in this object
     */
    protected function _loadAllTraversable()
    {
    	return $this->data;
    }
}