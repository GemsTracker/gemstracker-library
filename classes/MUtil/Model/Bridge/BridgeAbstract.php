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
 * @subpackage Model_Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 J-POP Foundation
 * @license    no free license, do not use without permission
 * @version    $id: HtmlFormatter.php 203 2013-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @copyright  Copyright (c) 2014 J-POP Foundation
 * @license    no free license, do not use without permission
 * @since      Class available since 2014 $(date} 22:00:02
 */
abstract class MUtil_Model_Bridge_BridgeAbstract extends MUtil_Translate_TranslateableAbstract
    implements MUtil_Model_Bridge_BridgeInterface
{
    /**
     * Mode when all output is lazy until rendering
     */
    const MODE_LAZY = 0;

    /**
     * Mode when all rows are preloaded using model->load()
     */
    const MODE_ROWS = 1;

    /**
     * Mode when only a single row is loaded using model->loadFirst()
     */
    const MODE_SINGLE_ROW = 2;

    /**
     *
     * @var MUtil_Model_Bridge_BridgeAbstract
     */
    protected $_chainedBridge;

    /**
     * Field name => compiled result, i.e. array of functions to call with only the value as parameter
     *
     * @var array
     */
    protected $_compilations = array();

    /**
     * Nested array or single row, depending on mode
     *
     * @var array
     */
    protected $_data;

    /**
     * A lazy repeater
     *
     * @var MUtil_Lazy_RepeatableInterface
     */
    protected $_repeater;

    /**
     * Omde of the self::MODE constants
     *
     * @var int
     */
    protected $mode = self::MODE_LAZY;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Construct the bridge while setting the model.
     *
     * Extra parameters can be added in subclasses, but the first parameter
     * must remain the model.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    public function __construct(MUtil_Model_ModelAbstract $model)
    {
        $this->setModel($model);
    }

    /**
     * Returns a formatted value or a lazy call to that function,
     * depending on the mode.
     *
     * @param string $name The field name or key name
     * @return mixed Lazy unless in single row mode
     * @throws MUtil_Model_ModelException
     */
    public function __get($name)
    {
        return $this->getFormatted($name);
    }

    /**
     * Checks name for being a key id field and in that case returns the real field name
     *
     * @param string $name The field name or key name
     * @param boolean $throwError By default we throw an error until rendering
     * @return string The real name and not e.g. the key id
     * @throws MUtil_Model_ModelException
     */
    protected function _checkName($name, $throwError = true)
    {
        if ($this->model->has($name)) {
            return $name;
        }

        $modelKeys = $this->model->getKeys();
        if (isset($modelKeys[$name])) {
            return $modelKeys[$name];
        }

        if ($throwError) {
            throw new MUtil_Model_ModelException(
                    sprintf('Request for unknown item %s from model %s.', $name, $this->model->getName())
                    );
        }

        return $name;
    }

    /**
     * Return an array of functions used to process the value
     *
     * @param string $name The real name and not e.g. the key id
     * @return array
     */
    abstract protected function _compile($name);

    /**
     * Format a value using the rules for the specified name.
     *
     * This is the workhouse function for the foematter and can
     * also be used with data not loaded from the model.
     *
     * @param string $name The real name and not e.g. the key id
     * @param mixed $value
     * @return mixed
     */
    public function format($name, $value)
    {
        if (!array_key_exists($name, $this->_compilations)) {
            if ($this->_chainedBridge) {
                $this->_compilations[$name] = array_merge(
                        $this->_chainedBridge->_compile($name),
                        $this->_compile($name)
                        );
            } else {
                $this->_compilations[$name] = $this->_compile($name);
            }
        }

        foreach ($this->_compilations[$name] as $function) {
            $value = call_user_func($function, $value);
        }

        return $value;
    }

    /**
     * Returns a formatted value or a lazy call to that function,
     * depending on the mode.
     *
     * @param string $name The field name or key name
     * @return mixed Lazy unless in single row mode
     * @throws MUtil_Model_ModelException
     */
    public function getFormatted($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        $fieldName = $this->_checkName($name);

        // Make sure the field is in the trackUsage fields list
        $this->model->get($fieldName);

        if ((self::MODE_SINGLE_ROW === $this->mode) && isset($this->_data[$fieldName])) {
            $this->$name = $this->format($fieldName, $this->_data[$fieldName]);
        } else {
            $this->$name = MUtil_Lazy::call(array($this, 'format'), $fieldName, $this->getLazy($fieldName));
        }
        if ($fieldName !== $name) {
            $this->model->get($name);
            $this->$fieldName = $this->$name;
        }

        return $this->$name;
    }

    /**
     * Return the lazy value without any processing.
     *
     * @param string $name The field name or key name
     * @return MUtil_Lazy_Call
     */
    public function getLazy($name)
    {
        return MUtil_Lazy::call(array($this, 'getLazyValue'), $name);
    }

    /**
     * Get the repeater result for
     *
     * @param string $name The field name or key name
     * @return mixed The result for name
     */
    public function getLazyValue($name)
    {
        $name = $this->_checkName($name, false);

        if (! $this->_repeater) {
            $this->getRepeater();
        }

        $current = $this->_repeater->__current();
        if ($current && isset($current->$name)) {
            return $current->$name;
        }
    }

    /**
     * Get the mode to one of Lazy (works with any other mode), one single row or multi row mode.
     *
     * @return int On of the MODE_ constants
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     *
     * @return MUtil_Model_ModelAbstract
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the repeater source for the lazy data
     *
     * @return \MUtil_Lazy_RepeatableInterface
     */
    public function getRepeater()
    {
        if (! $this->_repeater) {
            if ($this->_chainedBridge && $this->_chainedBridge->hasRepeater()) {
                $this->setRepeater($this->_chainedBridge->getRepeater());
            } else {
                $this->setRepeater($this->model->loadRepeatable());
            }
        }

        return $this->_repeater;
    }

    /**
     * Switch to single row mode and return that row.
     *
     * @return array or false when no row was found
     * @throws MUtil_Model_ModelException
     */
    public function getRow()
    {
        $this->setMode(self::MODE_SINGLE_ROW);

        if (! is_array($this->_data)) {
            $this->setRow();
        }

        return $this->_data;
    }

    /**
     * Switch to multi rows mode and return that the rows.
     *
     * @return array Nested or empty when no rows were found
     * @throws MUtil_Model_ModelException
     */
    public function getRows()
    {
        $this->setMode(self::MODE_ROWS);

        if (! is_array($this->_data)) {
            $this->setRows();
        }

        return $this->_data;
    }

    /**
     *
     * @param strin $name
     * @return mixed Lazy unless in single row mode
     */
    public function getValue($name)
    {
        $name = $this->_checkName($name);

        if ((self::MODE_SINGLE_ROW === $this->mode) && isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        return $this->getLazy($name);
    }

    /**
     * Returns true if name is in the model
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        if ($this->model->has($name)) {
            return true;
        }

        $modelKeys = $this->model->getKeys();
        return (boolean) isset($modelKeys[$name]);
    }

    /**
     * is there a repeater source for the lazy data
     *
     * @return boolean
     */
    public function hasRepeater()
    {
        return $this->_repeater instanceof MUtil_Lazy_RepeatableInterface ||
                ($this->_chainedBridge && $this->_chainedBridge->hasRepeater());
    }

    /**
     * Set the mode to one of Lazy (works with any other mode), one single row or multi row mode.
     *
     * @param int $mode On of the MODE_ constants
     * @return \MUtil_Model_Format_DisplayFormatter (continuation pattern)
     * @throws \MUtil_Model_ModelException The mode can only be set once
     */
    public function setMode($mode)
    {
        if (($mode == $this->mode) || (self::MODE_LAZY == $this->mode)) {
            $this->mode = $mode;

            if ($this->_chainedBridge) {
                $this->_chainedBridge->mode = $this->mode;
            }

            return $this;
        }

        throw new MUtil_Model_ModelException("Illegal bridge mode set after mode had already been set.");
    }

    /**
     * Set the model to be used by the bridge.
     *
     * This method exist to allow overruling in implementation classes
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Model_Bridge_BridgeAbstract (continuation pattern)
     */
    public function setModel(MUtil_Model_ModelAbstract $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the repeater source for the lazy data
     *
     * @param mixed $repeater MUtil_Lazy_RepeatableInterface or something that can be made into one.
     * @return \MUtil_Model_Format_DisplayFormatter (continuation pattern)
     */
    public function setRepeater($repeater)
    {
        if (! $repeater instanceof MUtil_Lazy_RepeatableInterface) {
            $repeater = new MUtil_Lazy_Repeatable($repeater);
        }
        $this->_repeater = $repeater;
        if ($this->_chainedBridge) {
            $this->_chainedBridge->_repeater = $repeater;
        }

        return $this;
    }

    /**
     * Switch to single row mode and set that row.
     *
     * @param array $row Or load from model
     * @return \MUtil_Model_Format_DisplayFormatter (continuation pattern)
     * @throws MUtil_Model_ModelException
     */
    public function setRow(array $row = null)
    {
        $this->setMode(self::MODE_SINGLE_ROW);

        if (null === $row) {
            $row = $this->model->loadFirst();

            if (! $row) {
                $row = array();
            }
        }

        $this->_data = $row;
        if ($this->_chainedBridge) {
            $this->_chainedBridge->_data = $this->_data;
        }

        $this->setRepeater(array($this->_data));

        return $this;
    }

    /**
     * Switch to multi rows mode and set those rows.
     *
     * @param array $rows Or load from model
     * @return \MUtil_Model_Format_DisplayFormatter (continuation pattern)
     * @throws MUtil_Model_ModelException
     */
    public function setRows(array $rows = null)
    {
        $this->setMode(self::MODE_ROWS);

        if (null === $rows) {
            if ($this->_repeater) {
                $rows = $this->_repeater->__getRepeatable();
            } else {
                $rows = $this->model->load();
            }

            if (! $rows) {
                $rows = array();
            }
        }

        $this->_data = $rows;
        if ($this->_chainedBridge) {
            $this->_chainedBridge->_data = $this->_data;
        }

        $this->setRepeater($this->_data);

        return $this;
    }
}
