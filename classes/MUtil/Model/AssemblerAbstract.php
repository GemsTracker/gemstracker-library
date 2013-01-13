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
 * @version    $id: AssemblerAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Abstract implementation of AssemblerInterface, only _assmble() needs to be
 * implemented.
 *
 * This abstract class contains helper functions to facilitate working
 * with processors.
 *
 * @see MUtil_Model_AssemblerInterface
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
abstract class MUtil_Model_AssemblerAbstract implements MUtil_Model_AssemblerInterface
{
    /**
     *
     * @var mixed
     */
    protected $_data = array();

    /**
     *
     * @var array name => extra options for name
     */
    protected $_extraArgs = array();

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $_model;

    /**
     *
     * @var MUtil_Loader_PluginLoader
     */
    private $_processorLoader = array();

    /**
     *
     * @var array Of name => MUtil_Model_ProcessorInterface
     */
    protected $_processors = array();

    /**
     *
     * @var MUtil_Lazy_RepeatableInterface
     */
    protected $_repeater;

    /**
     *
     * @var array
     */
    protected $_row = array();

    /**
     * Create the processor for this name
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param string $name
     * @return MUtil_Model_ProcessorInterface or string or array for creation null when it does not exist
     */
    abstract protected function _assemble(MUtil_Model_ModelAbstract $model, $name);

    /**
     * Get the processed output of the input or a lazy object if the data is repeated
     * or not yet set using setRepeater() or setRow().
     *
     * @param string $name
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see MUtil_Args::pairs()
     *                            These setting are applied to the model.
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return mixed MUtil_Lazy_Call when not using setRow(), actual output otherwise
     */
    public function getOutput($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        // Process new settings
        if ($args = MUtil_Ra::pairs(func_get_args(), 1)) {
            if (! $this->_model) {
                throw new MUtil_Model_ModelException("Cannot use processor without a model.");
            }

            $this->_model->set($name, $args);
        }

        if (! $this->hasProcessor($name, $args)) {
            if ($this->_row) {
                if (isset($this->_row[$name])) {
                    return $this->_row[$name];
                }

                return null;
            }
            // Fall through and return lazy call

        } else {
            // Assembler should be created with current values
            $processor = $this->getProcessor($name);

            if ($this->_row) {
                $input = new MUtil_Model_Input($this->_model, $name, $this->_row);

                $processor->process($input);

                return $input->getOutput();
            }
        }

        return new MUtil_Lazy_Call(array($this, 'output'), array($name));
    }


    /**
     * Returns the plugin loader for processors.
     *
     * @return MUtil_Loader_PluginLoader
     */
    public function getProcessorLoader()
    {
        if (! $this->_processorLoader) {
            $this->setProcessorLoader(MUtil_Model::getProcessorLoader());
        }

        return $this->_processorLoader;
    }

    /**
     * Returns the processor for the name
     *
     * @param string $name
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see MUtil_Args::pairs()
     *                            These setting are applied to the model.
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return MUtil_Model_ProcessorInterface or null when it does not exist
     */
    public function getProcessor($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        // Process new settings
        if ($args = MUtil_Ra::pairs(func_get_args(), 1)) {
            if (! $this->_model) {
                throw new MUtil_Model_ModelException("Cannot use processor without a model.");
            }

            $this->_model->set($name, $args);
        }

        if ($this->hasProcessor($name)) {
            return $this->_processors[$name];
        }
    }

    /**
     * Returns true if a processor exist for $name
     *
     * @param string $name
     * @return boolean
     */
    public function hasProcessor($name)
    {
        if (! $this->_model) {
            throw new MUtil_Model_ModelException("Cannot use processor without a model.");
        }

        if (! array_key_exists($name, $this->_processors)) {
            // Default if nothing there
            $this->_processors[$name] = false;

            // Try to create one
            if ($this->_model->has($name)) {
                if ($processor = $this->_assemble($this->_model, $name)) {
                    $this->setProcessor($name, $processor);
                }
            }
        }

        return false !== $this->_processors[$name];
    }

    /**
     * Helper function for when using lazy output or late use of setRow()
     *
     * @param string $name
     * @return mixed
     */
    public function output($name)
    {
        if ($this->_row) {
            $data = $this->_row;
        } else {
            $data = $this->_repeater->__current();
        }

        if ($this->hasProcessor($name)) {
            $processor = $this->getProcessor($name);

            $input = new MUtil_Model_Input($this->_model, $name, $data);

            $processor->process($input);

            return $input->getOutput();

        } elseif (isset($data[$name])) {

            return $data[$name];
        }
    }

    /**
     * Set the model of this assembler
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setModel(MUtil_Model_ModelAbstract $model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Sets the plugin loader for processors.
     *
     * @param MUtil_Loader_PluginLoader $loader
     */
    public function setProcessorLoader(MUtil_Loader_PluginLoader $loader)
    {
        $this->_processorLoader = $loader;
    }

    /**
     * Set the processor for a name
     *
     * @param string $name
     * $param mixed $processor MUtil_Model_ProcessorInterface or string or array that can be used to create processor
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setProcessor($name,  $processor)
    {
        if (is_string($processor)) {
            $loader    = $this->getProcessorLoader();
            $processor = $loader->createClass($processor);

        } elseif (is_array($processor)) {
            $loader    = $this->getProcessorLoader();
            $arguments = $processor;
            $processor = array_shift($arguments);

            $processor = $loader->createClass($processor, $arguments);

        }

        if ($processor instanceof MUtil_Model_ProcessorInterface) {
            $this->_processors[$name] = $processor;

            return $this;
        }

        throw new MUtil_Model_ModelException("No valid processor set for '$name'.");
    }

    /**
     * Use this method when you want to repeat the output for each row when rendering.
     *
     * The assembler does not itself loop through the multiple rows, for that to happen
     * you need to place the outputs of the gets on something that has the same repeater
     * and does repeat it, e.g. an MUtil_Html object.
     *
     * Either setRepeater() or setRow() should be set. setRow() is dominant.
     *
     * @param mixed $repeater MUtil_Lazy_RepeatableInterface or something that can be made into one.
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setRepeater($repeater)
    {
        if ($repeater instanceof MUtil_Lazy_RepeatableInterface) {
            $this->_repeater = $repeater;
        } else {
            $this->_repeater = new MUtil_Lazy_Repeatable($repeater);
        }

        return $this;
    }

    /**
     * Use this method when using a single row of input, i.e. do nothing lazy
     * and just draw the current row.
     *
     * Either setRepeater() or setRow() should be set. setRow() is dominant.
     *
     * @param array $data An array with data.
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setRow(array $data)
    {
        $this->_row = $data;

        return $this;
    }
}
