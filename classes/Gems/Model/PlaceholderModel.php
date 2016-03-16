<?php
/**
 * A placeholder array model
 */
class Gems_Model_PlaceholderModel extends \MUtil_Model_ArrayModelAbstract
{
	protected $data;

	public function __construct($fieldArray, $data = array())
	{
		$this->data = $data;

		$this->setMulti($fieldArray);
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