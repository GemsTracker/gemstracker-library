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
abstract class ConditionAbstract extends \MUtil_Translate_TranslateableAbstract implements ConditionInterface
{
    protected $_data;

    /**
     *
     * @var \Gems\Conditions
     */
    protected $conditions;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    public function exchangeArray(array $data)
    {
        $this->_data = $data;
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
