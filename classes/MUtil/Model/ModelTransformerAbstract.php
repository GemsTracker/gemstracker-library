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
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ModelTransformerInterface.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * A general transformer that implements all required functions, without
 * them doing anything so you can just implement what you need.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2 (in current form)
 */
abstract class MUtil_Model_ModelTransformerAbstract implements MUtil_Model_ModelTransformerInterface
{
    /**
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Gets one or more values for a certain field name.
     *
     * @see MUtil_Model_ModelAbstract->get()
     *
     * @param string $name Field name
     * @param string|array|null $arrayOrKey1 Null or the name of a single attribute or an array of attribute names
     * @param string $key2 Optional a second attribute name.
     * @return mixed
     */
    public function get($name, $arrayOrKey1 = null, $key2 = null)
    {
        $args = func_get_args();
        $args = MUtil_Ra::args($args, 1);

        switch (count($args)) {
            case 0:
                if (isset($this->_fields[$name])) {
                    return $this->_fields[$name];
                } else {
                    return array();
                }

            case 1:
                $key = $arrayOrKey1;
                if (isset($this->_fields[$name][$arrayOrKey1])) {
                    return $this->_fields[$name][$arrayOrKey1];
                } else {
                    return null;
                }

            default:
                $results = array();
                foreach ($args as $key) {
                    if (isset($this->_fields[$name][$arrayOrKey1])) {
                        $results[$key] = $this->_fields[$name][$arrayOrKey1];
                    }
                }
                return $results;
        }
    }

    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged()
    {
        return 0;
    }

    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(MUtil_Model_ModelAbstract $model)
    {
        return $this->_fields;
    }

    /**
     * Set one or more attributes for a field names in the model.
     *
     * @see MUtil_Model_ModelAbstract->set()
     *
     * @param string $name The fieldname
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see MUtil_Args::pairs()
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return \MUtil_Model_ModelTransformerAbstract
     */
    public function set($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $args = func_get_args();
        $args = MUtil_Ra::pairs($args, 1);

        if ($args) {
            foreach ($args as $key => $value) {
                // If $key end with ] it is array value
                if (substr($key, -1) == ']') {
                    if (substr($key, -2) == '[]') {
                        // If $key ends with [], append it to array
                        $key    = substr($key, 0, -2);
                        $this->_fields[$name][$key][] = $value;
                    } else {
                        // Otherwise extract subkey
                        $pos    = strpos($key, '[');
                        $subkey = substr($key, $pos + 1, -1);
                        $key    = substr($key, 0, $pos);

                        $this->_fields[$name][$key][$subkey] = $value;
                    }
                } else {
                    $this->_fields[$name][$key] = $value;
                }
            }
        } elseif (!array_key_exists($name, $this->_fields)) {
            $this->_fields[$name] = array();
        }

        return $this;
    }

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(MUtil_Model_ModelAbstract $model, array $filter)
    {
        // No changes
        return $filter;
    }

    /**
     * This transform function checks the sort to
     * a) remove sorts from the main model that are not possible
     * b) add sorts that are required needed
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $sort
     * @return array The (optionally changed) sort
     */
    public function transformSort(MUtil_Model_ModelAbstract $model, array $sort)
    {
        // No changes
        return $sort;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        // No changes
        return $data;
    }

    /**
     * This transform function performs the actual save of the data and is called after
     * the saving of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MUtil_Model_ModelAbstract $model, array $row)
    {
        // No changes
        return $row;
    }
}
