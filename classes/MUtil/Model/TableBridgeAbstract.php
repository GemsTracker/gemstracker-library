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
 */

/**
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Model
 */
abstract class MUtil_Model_TableBridgeAbstract implements Gems_Menu_ParameterSourceInterface
{
    protected $model;
    protected $modelKeys;
    protected $repeater;

    /**
     * The actual table
     *
     * @var MUtil_Html_TableElement
     */
    protected $table;

    public function __call($name, array $arguments)
    {
        return call_user_func_array(array($this->table,  $name), $arguments);
    }

    public function __construct(MUtil_Model_ModelAbstract $model, $args_array = null)
    {
        $this->setModel($model);

        if ($args_array instanceof MUtil_Html_ElementInterface) {
            $this->table = $args_array;
        } else {
            $args = func_get_args();
            $args = MUtil_Ra::args($args, 1);

            $this->table = MUtil_Html::table($args);
        }
    }

    public function __get($name)
    {
        $this->_checkName($name);

        if (! $this->model->has($name)) {
            throw new MUtil_Model_ModelException(sprintf('Request for unknown item %s from model %s.', $name, $this->model->getName()));
        }

        $value = $this->getLazy($name);

        if ($multi = $this->model->get($name, 'multiOptions')) {
            $value = MUtil_Lazy::offsetGet($multi, $value);
        }

        if ($format = $this->model->get($name, 'dateFormat')) {
            if (is_callable($format)) {
                $value = MUtil_Lazy::call($format, $value);
            } else {
                $value = MUtil_Lazy::call('MUtil_Date::format', $value, $format, $this->model->get($name, 'storageFormat'));
            }
        }

        if ($function = $this->model->get($name, 'formatFunction')) {
            $value = MUtil_Lazy::call($function, $value);
        }

        if ($marker = $this->model->get($name, 'markCallback')) {
            $value = MUtil_Lazy::call($marker, $value);
        }

        // Cache for next call
        $this->$name = $value;

        return $value;
    }

    private static function _applyDisplayFunction($item, $function)
    {
        if (is_callable($function)) {
            return call_user_func($function, $item);
        }

        if (is_object($function)) {
            if (($function instanceof MUtil_Html_ElementInterface)
                || method_exists($function, 'append')) {

                $object = clone $function;

                $object->append($item);

                return $object;
            }
        }

        // Assume it is a html tag when a string
        if (is_string($function)) {

            return MUtil_Html::create($function, $item);

        } elseif (is_array($function)) {
            foreach ($function as $display) {
                $item = self::_applyDisplayFunction($item, $display);
            }
        }

        return $item;
    }

    private function _applyDisplayFunctions($name, $item, $forHeader = false)
    {
        if (is_string($name)) {

            if ($forHeader) {
                $displays[] = $this->model->get($name, 'tableHeaderDisplay');
            } else {
                $displays[] = $this->model->get($name, 'itemDisplay');
            }
            $displays[] = $this->model->get($name, 'tableDisplay');

            $item = self::_applyDisplayFunction($item, $displays);
        }

        return $item;
    }

    protected function _checkLabel($label, $name)
    {
        if (is_string($name)) {
            if (null === $label) {
                $label = $this->model->get($name, 'label');
            }

            $label = $this->_applyDisplayFunctions($name, $label, true);
        }

        return $label;
    }

    protected function _checkName(&$name)
    {
        if (isset($this->modelKeys[$name])) {
            $name = $this->modelKeys[$name];
        }

        return $name;
    }

    protected function _getLazyName($name)
    {
        if (is_string($name)) {
            return $this->_applyDisplayFunctions($name, $this->$name, false);
        }

        return $name;
    }

    public function getLazy($name)
    {
        if (! $this->repeater) {
            $this->repeater = $this->table->getRepeater();

            if (! $this->repeater) {
                // Wait with
                return MUtil_Lazy::method($this, 'getLazyValue', $name);
            }
        }

        return $this->repeater->$name;
    }

    public function getLazyValue($name)
    {
        if (! $this->repeater) {
            $this->repeater = $this->table->getRepeater();

            if (! $this->repeater) {
                $this->repeater = $this->model->loadRepeatable();
                $this->table->getRepeater($this->repeater);
            }
        }

        // We are no longer being lazy.
        // But there may not be a current value.
        if ($current = $this->repeater->__current()) {
            return $current->$name;
        }
    }

    public function getMenuParameter($name, $default)
    {
        $this->_checkName($name);

        if ($this->model->has($name)) {
            return $this->getLazy($name);
        }

        return $default;
    }

    abstract public function getTable();

    abstract public function itemIf($if, $item);

    /**
     * Set the model to use in the tablebridge
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Model_TableBridgeAbstract
     */
    public function setModel(MUtil_Model_ModelAbstract $model)
    {
        $this->model     = $model;
        $this->modelKeys = $this->model->getKeys();

        return $this;
    }
}