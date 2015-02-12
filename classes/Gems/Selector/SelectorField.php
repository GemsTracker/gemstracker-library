<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Selector_SelectorField
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

    public function getHRef(MUtil_Lazy_RepeatableInterface $repeater, array $url = array())
    {
        $url[\Gems_Selector_DateSelectorAbstract::DATE_FACTOR]       = $repeater->date_factor;
        $url[\Gems_Selector_DateSelectorAbstract::DATE_GROUP]        = $this->name;
        $url[\Gems_Snippets_AutosearchFormSnippet::AUTOSEARCH_RESET] = null;
        return new MUtil_Html_HrefArrayAttribute($url);
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
     * @return Gems_Selector_SelectorField
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
     * @return Gems_Selector_SelectorField
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     *
     * @param string $class
     * @return Gems_Selector_SelectorField
     */
    public function setLabelClass($class)
    {
        $this->labelClass = $class;

        return $this;
    }

    /**
     *
     * @param string|Zend_Db_Expr $sql
     * @return Gems_Selector_SelectorField
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
     * @return Gems_Selector_SelectorField
     */
    public function setSQLFunction($function, $calculation, $round = null, $default = null)
    {
        $this->default = $default;

        $sql = "$function($calculation)";
        if (null !== $round) {
            $sql = "ROUND($sql, $round)";
        }
        return $this->setSQL(new Zend_Db_Expr($sql));
    }

    /**
     *
     * @param string $calculation
     * @param integer|null $round
     * @param mixed $default
     * @return Gems_Selector_SelectorField
     */
    public function setToAverage($calculation, $round = null, $default = null)
    {
        return $this->setSQLFunction('AVG', $calculation, $round, $default);
    }

    /**
     *
     * @param string $calculation
     * @param mixed $default
     * @return Gems_Selector_SelectorField
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
     * @return Gems_Selector_SelectorField
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
     * @return Gems_Selector_SelectorField
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
     * @return Gems_Selector_SelectorField
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
     * @return Gems_Selector_SelectorField
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
