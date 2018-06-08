<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
abstract class RoundConditionAbstract extends \MUtil_Translate_TranslateableAbstract implements RoundConditionInterface
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
