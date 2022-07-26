<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Selector;

/**
 *
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class SelectorField
{
    protected $class;
    protected $default;
    protected $filter;
    protected $label;
    protected $labelClass;
    protected $name;
    protected $sql;

    public function  __construct($name)
    {
        $this->name = $name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getHRef(\MUtil\Lazy\RepeatableInterface $repeater, array $url = array())
    {
        $url[\Gems\Selector\DateSelectorAbstract::DATE_FACTOR] = $repeater->date_factor;
        $url[\Gems\Selector\DateSelectorAbstract::DATE_GROUP]  = $this->name;
        $url[\MUtil\Model::AUTOSEARCH_RESET]                   = null;
        return new \MUtil\Html\HrefArrayAttribute($url);
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getLabelClass()
    {
        return $this->labelClass;
    }

    public function getSQL()
    {
        return $this->sql;
    }

    /**
     *
     * @param string $class
     * @return \Gems\Selector\SelectorField
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     *
     * @param string $label
     * @return \Gems\Selector\SelectorField
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     *
     * @param string $class
     * @return \Gems\Selector\SelectorField
     */
    public function setLabelClass($class)
    {
        $this->labelClass = $class;

        return $this;
    }

    /**
     *
     * @param string|\Zend_Db_Expr $sql
     * @return \Gems\Selector\SelectorField
     */
    public function setSQL($sql)
    {
        $this->sql = $sql;

        return $this;
    }

    /**
     *
     * @param string $function
     * @param string $calculation
     * @param string $round
     * @return \Gems\Selector\SelectorField
     */
    public function setSQLFunction($function, $calculation, $round = null, $default = null)
    {
        $this->default = $default;

        $sql = "$function($calculation)";
        if (null !== $round) {
            $sql = "ROUND($sql, $round)";
        }
        return $this->setSQL(new \Zend_Db_Expr($sql));
    }

    /**
     *
     * @param string $calculation
     * @param integer|null $round
     * @param mixed $default
     * @return \Gems\Selector\SelectorField
     */
    public function setToAverage($calculation, $round = null, $default = null)
    {
        return $this->setSQLFunction('AVG', $calculation, $round, $default);
    }

    /**
     *
     * @param string $calculation
     * @param mixed $default
     * @return \Gems\Selector\SelectorField
     */
    public function setToCount($calculation = '*', $default = 0)
    {
        return $this->setSQLFunction('COUNT', $calculation, null, $default);
    }

    /**
     *
     * @param string $calculation
     * @param integer|null $round
     * @param mixed $default
     * @return \Gems\Selector\SelectorField
     */
    public function setToMaximum($calculation, $round = null, $default = null)
    {
        return $this->setSQLFunction('MAX', $calculation, $round, $default);
    }

    /**
     *
     * @param string $calculation
     * @param integer|null $round
     * @param mixed $default
     * @return \Gems\Selector\SelectorField
     */
    public function setToMinimum($calculation, $round = null, $default = null)
    {
        return $this->setSQLFunction('MIN', $calculation, $round, $default);
    }

    /**
     *
     * @param string $calculation
     * @param integer|null $round
     * @param mixed $default
     * @return \Gems\Selector\SelectorField
     */
    public function setToSum($calculation, $round = null, $default = 0)
    {
        return $this->setSQLFunction('SUM', $calculation, $round, $default);
    }

    /**
     *
     * @param string $condition
     * @param mixed $field The field or constant to add to the sum when $condition is true.
     * @param integer|null $round
     * @param mixed $default
     * @return \Gems\Selector\SelectorField
     */
    public function setToSumWhen($condition, $field = 1, $round = null, $default = 0)
    {
        if (is_numeric($field)) {
            $this->setFilter($condition);
        } else {
            $this->setFilter("($condition) AND ($field != 0)");
        }
        return $this->setSQLFunction('SUM', "CASE WHEN $condition THEN $field ELSE 0 END", $round, $default);
    }
}
