<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 *
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: Crop.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Model_Input
{
    /**
     * The data incuding $this->_context[$this->_name]
     *
     * @var array
     */
    protected $_context;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $_model;

    /**
     * The name of the item being garvested
     *
     * @var string
     */
    protected $_name;

    /**
     *
     * @var array
     */
    protected $_options = array();

    /**
     * The original begin value
     *
     * @var mixed
     */
    protected $_origValue;

    /**
     * The current output result
     *
     * @var mixed
     */
    protected $_output;

    /**
     *
     * @param MUtil_Model_ModelAbstract $model The model used
     * @param string $name The item being cropped
     * @param array $context The current data
     */
    public function __construct(MUtil_Model_ModelAbstract $model, $name, array $context = array())
    {
        // Should always exist
        if (! array_key_exists($name, $context)) {
            $content[$name] = null;
        }

        $this->_context   = $context;
        $this->_model     = $model;
        $this->_name      = $name;
        $this->_origValue = $context[$name];
        $this->_output    = $this->_origValue;
    }

    /**
     * Returns a context item or the whole context array when no name
     * is given.
     *
     * @param string $name Optional name of context item
     * @return mixed
     */
    public function getContext($name = null)
    {
        if (null === $name) {
            return $this->_context;
        }

        if (array_key_exists($name, $this->_context)) {
            return $this->_context[$name];
        }
    }

    /**
     *
     * @return string The name of the current item
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return a single option value
     *
     * @param string $name The name of an option item
     * @return mixed
     */
    public function getOption($name)
    {
        if (array_key_exists($name, $this->_options)) {
            return $this->_options[$name];
        } else {
            return $this->_model->get($this->_name, $name);
        }
    }

    /**
     * Return all or a named subset of options
     *
     * @param array $names Optional: an array of names to get the options from
     * @return array
     */
    public function getOptions(array $names = null)
    {
        if (null === $names) {
            // Return all
            return $this->_options + $this->_model->get($this->_name);
        }

        $result = array();
        foreach ($names as $name) {
            $result[$name] = $this->getOption($name);
        }
        return $result;
    }

    /**
     * Return the original value
     *
     * @return mixed
     */
    public function getOriginalValue()
    {
        return $this->_origValue;
    }

    /**
     * Return the current output
     *
     * @return mixed
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * Set a context item or replace the whole context array when an array is passed.
     *
     * @param mixed $nameOrArray The name of context item to set or an array containing a new context
     * @param mixed $value The value to set
     * @return MUtil_Model_Crop (continuation pattern)
     */
    public function setContext($nameOrArray, $value = null)
    {
        if (is_array($nameOrArray)) {
            $this->_context = $nameOrArray;
        } else {
            $this->_context[$nameOrArray] = $value;
        }

        return $this;
    }

    /**
     * Set an option item.
     *
     * @param mixed $name The name of option item to set
     * @param mixed $value The value to set
     * @return MUtil_Model_Crop (continuation pattern)
     */
    public function setOption($name, $value = null)
    {
        // If $key end with ] it is array value
        if (substr($name, -1) == ']') {
            // Load from original model
            if (! isset($this->_options[$name])) {
                $this->_options[$name] = $this->_model->get($this->_name, $name);
            }

            if (substr($name, -2) == '[]') {
                // If $name ends with [], append it to array
                $name = substr($name, 0, -2);
                $this->_options[$name][] = $value;
            } else {
                // Otherwise extract subkey
                $pos    = strpos($name, '[');
                $subkey = substr($name, $pos + 1, -1);
                $name   = substr($name, 0, $pos);

                $this->_options[$name][$subkey] = $value;
            }
        } else {
            $this->_options[$name] = $value;
        }

        return $this;
    }

    /**
     * Set a context item or replace the whole context array when an array is passed.
     *
     * @param array $options An array containing a new options
     * @return MUtil_Model_Crop (continuation pattern)
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * The output result value of this crop item
     *
     * @param mixed $output The new output result
     * @return MUtil_Model_Crop (continuation pattern)
     */
    public function setOutput($output)
    {
        $this->_output = $output;

        return $this;
    }
}
