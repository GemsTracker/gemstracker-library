<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Model_Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: JsonData.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace MUtil\Model\Type;

/**
 *
 *
 * @package    MUtil
 * @subpackage Model_Type
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.7.1 16-apr-2015 15:30:45
 */
class JsonData
{
    /**
     * Maximum number of items in table display
     * @var int
     */
    private $_maxTable = 3;

    /**
     * Show there are more items
     *
     * @var string
     */
    private $_more = '...';

    /**
     * The separator for the table items
     *
     * @var string
     */
    private $_separator;

    /**
     *
     * @param int $maxTable Max number of rows to display in table display
     * @param string $separator Separator in table display
     * @param string $more There is more in table display
     */
    public function __construct($maxTable = 3, $separator = '<br />', $more = '...')
    {
        $this->_maxTable  = $maxTable;
        $this->_more      = $more;
        $this->_separator = $separator;
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string $name The field to set the seperator character
     * @param boolean $detailed When true show detailed information
     * @return \MUtil_Model_Type_ConcatenatedRow (continuation pattern)
     */
    public function apply(\MUtil_Model_ModelAbstract $model, $name, $detailed)
    {
        if ($detailed) {
            $formatFunction = 'formatDetailed';
        } else {
            $formatFunction = 'formatTable';
        }
        $model->set($name, 'formatFunction', array($this, $formatFunction));
        $model->setOnLoad($name, array($this, 'loadValue'));
        $model->setOnSave($name, array($this, 'saveValue'));
    }

    /**
     * Displays the content
     *
     * @param string $value
     * @return string
     */
    public function formatDetailed($value)
    {
        if ((null === $value) || is_scalar($value)) {
            return $value;
        }

        return \MUtil_Html_TableElement::createArray($value);
    }

    /**
     * Displays the content
     *
     * @param string $value
     * @return string
     */
    public function formatTable($value)
    {
        if ((null === $value) || is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            $i = 0;
            $output = new \MUtil_Html_Sequence();
            $output->setGlue($this->_separator);
            foreach ($value as $val) {
                if ($i++ > $this->_maxTable) {
                    $output->append($this->_more);
                    break;
                }
                $output->append($val);
            }
            return $output;
        }
        return \MUtil_Html_TableElement::createArray($value);
    }

    /**
     * A ModelAbstract->setOnLoad() function that concatenates the
     * value if it is an array.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return array Of the values
     */
    public function loadValue($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        return json_decode($value, true);
    }

    /**
     * A ModelAbstract->setOnSave() function that concatenates the
     * value if it is an array.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string Of the values concatenated
     */
    public function saveValue($value, $isNew = false, $name = null, array $context = array())
    {
        return json_encode($value);
    }
}
