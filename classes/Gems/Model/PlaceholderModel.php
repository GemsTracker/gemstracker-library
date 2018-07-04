<?php
/**
 * A placeholder array model
 */
class Gems_Model_PlaceholderModel extends \MUtil_Model_ArrayModelAbstract
{
	protected $data;

	public function __construct($modelName, $fieldArray, $data = array())
	{
        parent::__construct($modelName);
        
		$this->data = $data;

		$this->setMulti($fieldArray);
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