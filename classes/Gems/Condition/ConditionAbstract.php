<?php

/**
 *
 *
 * @package    Gems
 * @subpackage Condition
 * @author     mjong
 * @license    Not licensed, do not copy
 */

namespace Gems\Condition;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @since      Class available since version 1.8.8
 */
abstract class ConditionAbstract extends \MUtil\Translate\TranslateableAbstract implements ConditionInterface
{
    protected $_data;

    /**
     *
     * @var \Gems\Conditions
     */
    protected $conditions;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @inheritDoc
     * /
    public function afterRegistry()
    {
        parent::afterRegistry();
    }

    /**
     * @param array $data
     */
    public function exchangeArray(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Get the condition id for this condition
     *
     * @return int
     */
    public function getConditionId()
    {
        return $this->_data['gcon_id'];
    }

    /**
     * Return a comparator
     *
     * @return \Gems\Condition\Comparator\ComparatorInterface
     */
    public function getComparator($name, $options = array())
    {
        return $this->conditions->loadComparator($name, $options);
    }

}
