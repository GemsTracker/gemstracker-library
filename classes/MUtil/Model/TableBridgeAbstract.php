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
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Model_TableBridgeAbstract
{
    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var type
     */
    protected $repeater;

    /**
     * The actual table
     *
     * @var MUtil_Html_TableElement
     */
    protected $table;

    /**
     * Cascades call's to the underlying table
     *
     * @param string $name
     * @param array $arguments
     * @return mixes
     */
    public function __call($name, array $arguments)
    {
        return call_user_func_array(array($this->table,  $name), $arguments);
    }

    /**
     * Constructs a bridge for a model
     *
     * @param MUtil_Model_ModelAbstract $model The model it is all about
     * @param MUtil_Html_ElementInterface $args_array
     */
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

    /**
     * Returns a lazy variable for this field.
     *
     * Lazy means that the value is esthablished only when rendering.
     * When redering this value with a repeater it will return a different
     * value for every row.
     *
     * @param string $name
     * @return MUtil_Lazy_Abstract
     * @throws MUtil_Model_ModelException
     */
    public function __get($name)
    {
        $this->_checkName($name);

        if (! $this->model->has($name)) {
            throw new MUtil_Model_ModelException(
                    sprintf('Request for unknown item %s from model %s.', $name, $this->model->getName())
                    );
        }

        $value = $this->getLazy($name);

        if ($multi = $this->model->get($name, 'multiOptions')) {
            $value = MUtil_Lazy::offsetGet($multi, $value);
        }

        if ($function = $this->model->get($name, 'formatFunction')) {
            $value = MUtil_Lazy::call($function, $value);
        } elseif ($format = $this->model->get($name, 'dateFormat')) {
            if (is_callable($format)) {
                $value = MUtil_Lazy::call($format, $value);
            } else {
                $value = MUtil_Lazy::call('MUtil_Date::format', $value, $format, $this->model->get($name, 'storageFormat'));
            }
        }

        if ($marker = $this->model->get($name, 'markCallback')) {
            $value = MUtil_Lazy::call($marker, $value);
        }

        // Cache for next call
        $this->$name = $value;

        return $value;
    }

    /**
     * Display the item correctly using the function
     *
     * @param mixed $item
     * @param mxied $function When array each element is applied, when function it is executed,
     * otherwise it is added to an HtmlElement
     * @return \MUtil_Html_ElementInterface
     */
    private static function _applyDisplayFunction($item, $function)
    {
        // MUtil_Echo::track($function);
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
                if ($display !== null) {
                    $item = self::_applyDisplayFunction($item, $display);
                }
            }
        }

        return $item;
    }

    /**
     * Returns a lazy version of the item wrapped in all the functions needed to
     * display it correctly.
     *
     * @param string $name name of the model item
     * @param mixed $item Lazy variable or a label
     * @param booelan $forHeader if true uses header settings tableHeaderDisplay instead of itemDisplay
     * @return type
     */
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

    /**
     * Return a lavel with optionally some display functions
     * from the model wrapped around it lazely.
     *
     * @param mixed $label
     * @param string $name
     * @return mixed
     */
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

    /**
     * Checks name for being a key id field and in that case returns the real field name
     *
     * @param string $name
     * @return string
     */
    protected function _checkName(&$name)
    {
        $modelKeys = $this->model->getKeys();
        if (isset($modelKeys[$name])) {
            $name = $modelKeys[$name];
        }

        return $name;
    }

    /**
     *
     * @param string $name
     * @return MUtil_Lazy_LazyAbstract
     */
    protected function _getLazyName($name)
    {
        if (is_string($name)) {
            return $this->_applyDisplayFunctions($name, $this->$name, false);
        }

        return $name;
    }

    /**
     * Return the lazy value without any makeup.
     *
     * @param string $name
     * @return MUtil_Lazy_LazyAbstract
     */
    public function getLazy($name)
    {
        $this->_checkName($name);

        if (! $this->repeater) {
            $this->repeater = $this->table->getRepeater();

            if (! $this->repeater) {
                // Wait with
                return MUtil_Lazy::method($this, 'getLazyValue', $name);
            }
        }

        return $this->repeater->$name;
    }

    /**
     * Returns the real underlying value. Not the lazy object.
     *
     * @param string $name
     * @return mxied
     */
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
            if (!$current->offsetExists($name)) {
                if (MUtil_Model::$verbose) {
                    MUtil_Echo::r(sprintf("Field %s missing in model %s.", $name, $this->model->getName()));
                }
                return null;
            }
            return $current->offsetGet($name);
        }
    }

    /**
     * Get the actual table
     *
     * @return MUtil_Html_TableElement
     */
    abstract public function getTable();

    /**
     * Returns true if name is in the model
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        $this->_checkName($name);

        return $this->model->has($name);
    }

    /**
     * Add an item based of a lazy if
     *
     * @param mixed $if
     * @param mixed $item
     * @param mixed $else
     * @return array
     */
    abstract public function itemIf($if, $item, $else = null);

    /**
     * Set the model to use in the tablebridge
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Model_TableBridgeAbstract
     */
    public function setModel(MUtil_Model_ModelAbstract $model)
    {
        $this->model     = $model;

        return $this;
    }
}