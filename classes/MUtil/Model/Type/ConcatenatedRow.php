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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Model_Type_ConcatenatedRow
{
    /**
     * The character used to separate values when displaying.
     *
     * @var string
     */
    protected $displaySeperator = ' ';

    /**
     * Optional multi options to use
     *
     * @var array
     */
    protected $options;

    /**
     * The character used to separate values when storing.
     *
     * @var string
     */
    protected $seperatorChar = ' ';

    /**
     * When true the value is padded on both sides with the $seperatorChar.
     *
     * Makes it easier to filter.
     *
     * @var boolean
     */
    protected $valuePad = true;

    /**
     * \MUtil_Ra::args() parameter passing is allowed.
     *
     * @param string $seperatorChar
     * @param string $displaySeperator
     * @param boolean $valuePad
     */
    public function __construct($seperatorChar = ' ', $displaySeperator = ' ', $valuePad = true)
    {
        $args = \MUtil_Ra::args(
                func_get_args(),
                array(
                    'seperatorChar' => 'is_string',
                    'displaySeperator' => array('MUtil_Html_HtmlInterface', 'is_string'),
                    'valuePad' => 'is_boolean',
                    ),
                array('seperatorChar' => ' ', 'displaySeperator' => ' ', 'valuePad' => true)
                );

        $this->seperatorChar    = substr($args['seperatorChar'] . ' ', 0, 1);
        $this->displaySeperator = $args['displaySeperator'];
        $this->valuePad         = $args['valuePad'];
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string $name The field to set the seperator character
     * @return \MUtil_Model_Type_ConcatenatedRow (continuation pattern)
     */
    public function apply(\MUtil_Model_ModelAbstract $model, $name)
    {
        $model->set($name, 'formatFunction', array($this, 'format'));
        $model->setOnLoad($name, array($this, 'loadValue'));
        $model->setOnSave($name, array($this, 'saveValue'));

        if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
            $model->setOnTextFilter($name, array($this, 'textFilter'));
        }

        $this->options = $model->get($name, 'multiOptions');
        return $this;
    }


    /**
     * Displays the content
     *
     * @param string $value
     * @return string
     */
    public function format($value)
    {
        // \MUtil_Echo::track($value, $this->options);
        if (! is_array($value)) {
            $value = $this->loadValue($value);
        }
        if (is_array($value)) {
            if ($this->options) {
                foreach ($value as &$val) {
                    if (isset($this->options[$val])) {
                        $val = $this->options[$val];
                    }
                 }
            }
            if (is_string($this->displaySeperator)) {
                return implode($this->displaySeperator, $value);
            } else {
                $output = new \MUtil_Html_Sequence($value);
                $output->setGlue($this->displaySeperator);
                return $output;
            }
        }
        if (isset($this->options[$value])) {
            return $this->options[$value];
        }
        return $value;
    }

    /**
     * If this field is saved as an array value, use
     *
     * @return array Containing settings for model item
     */
    public function getSettings()
    {
        $output['formatFunction'] = array($this, 'format');
        $output[\MUtil_Model_ModelAbstract::LOAD_TRANSFORMER] = array($this, 'loadValue');
        $output[\MUtil_Model_ModelAbstract::SAVE_TRANSFORMER] = array($this, 'saveValue');
        $output[\MUtil_Model_DatabaseModelAbstract::TEXTFILTER_TRANSFORMER] = array($this, 'textFilter');

        return $output;
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
        // \MUtil_Echo::track($value, $name, $context);
        if (! is_array($value)) {
            if ($this->valuePad) {
                $value = trim($value, $this->seperatorChar);
            }
            // If it was empty, return an empty array instead of array with an empty element
            if(empty($value)) {
                return array();
            }
            $value = explode($this->seperatorChar, $value);
        }
        // \MUtil_Echo::track($value);

        return $value;
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
        // \MUtil_Echo::track($value);
        if (is_array($value)) {
            $value = implode($this->seperatorChar, $value);

            if ($this->valuePad) {
                $value = $this->seperatorChar . $value . $this->seperatorChar;
            }
        }
        return $value;
    }

    /**
     *
     * @param string $filter The text to filter for
     * @param string $name The model field name
     * @param string $sqlField The SQL field name
     * @param \MUtil_Model_DatabaseModelAbstract $model
     * @return array Array of OR-filter statements
     */
    public function textFilter($filter, $name, $sqlField, \MUtil_Model_DatabaseModelAbstract $model)
    {
        if ($options = $model->get($name, 'multiOptions')) {
            $adapter = $model->getAdapter();
            $wheres = array();
            foreach ($options as $key => $value) {
                // \MUtil_Echo::track($key, $value, $filter, stripos($value, $filter));
                if (stripos($value, $filter) !== false) {
                    if (null === $key) {
                        $wheres[] = $sqlField . ' IS NULL';
                    } else {
                        $quoted   = $adapter->quote($key);
                        $wheres[] = $sqlField . " LIKE '%" . $this->seperatorChar . $quoted . $this->seperatorChar . "%'";

                        if (! $this->valuePad) {
                            // Add other options
                            $wheres[] = $sqlField . " LIKE '" . $quoted . $this->seperatorChar . "%'";
                            $wheres[] = $sqlField . " LIKE '%" . $this->seperatorChar . $quoted . "'";
                            $wheres[] = $sqlField . " = " . $quoted;
                        }
                    }
                }
            }
            return $wheres;
        }
    }
}
