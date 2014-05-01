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
 * @subpackage Model_Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Model_Bridge_TableBridgeAbstract extends MUtil_Model_Bridge_BridgeAbstract
//    implements MUtil_Model_Bridge_BridgeInterface
{
    /**
     *
     * @var MUtil_Model_ModelAbstract
     * /
    protected $model;

    /**
     *
     * @var type
     * /
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
        parent::__construct($model);

        $this->_chainedBridge = $model->getBridgeFor('display');

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
     * /
    public function __get($name)
    {
        $this->$name = $this->_displayBridge->$name;
        return $this->$name;
        /*
        $this->_checkName($name);

        if (! $this->model->has($name)) {
            throw new MUtil_Model_ModelException(
                    sprintf('Request for unknown item %s from model %s.', $name, $this->model->getName())
                    );
        }

        $value = $this->getLazy($name);

        if ($this->model->get($name, 'multiOptions')) {
            $value = MUtil_Lazy::offsetGet(
                    MUtil_Lazy::call(array($this->model, 'get'), $name, 'multiOptions'),
                    $value
                    );
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
        // * /
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
     * Return an array of functions used to process the value
     *
     * @param string $name The real name and not e.g. the key id
     * @return array
     */
    protected function _compile($name)
    {
        return array();
    }

    /**
     * Checks name for being a key id field and in that case returns the real field name
     *
     * @param string $name
     * @return string
     * /
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
     * /
    public function getLazy($name)
    {
        $this->_checkName($name);

        if (! $this->repeater) {
            $this->repeater = $this->table->getRepeater();

            if (! $this->repeater) {
                if ($this->_displayBridge->hasRepeater()) {
                    $this->repeater = $this->_displayBridge->getRepeater();
                } else {
                    // Wait with
                    return MUtil_Lazy::method($this, 'getLazyValue', $name);
                }
            }

            $this->_displayBridge->setRepeater($this->repeater);
        }

        return $this->repeater->$name;
    }

    /**
     * Returns the real underlying value. Not the lazy object.
     *
     * @param string $name
     * @return mixed
     * /
    public function getLazyValue($name)
    {
        if ($this->repeater && $this->_displayBridge->hasRepeater()) {
            //if ($this->repeater !== $this->_displayBridge->getRepeater()) {
            //    MUtil_Echo::track('Chakka!');
            //}
        }
        if (! $this->repeater) {
            $this->repeater = $this->table->getRepeater();

            if (! $this->repeater) {
                if ($this->_displayBridge->hasRepeater()) {
                    $this->repeater = $this->_displayBridge->getRepeater();
                } else {
                    $this->repeater = $this->model->loadRepeatable();
                    $this->table->setRepeater($this->repeater);
                }
            }
            $this->_displayBridge->setRepeater($this->repeater);
        }
        if (null === $name) {
            return null;
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
     * Get the repeater source for the lazy data
     *
     * @return \MUtil_Lazy_RepeatableInterface
     */
    public function getRepeater()
    {
        if ($this->_repeater) {
            return $this->_repeater;
        }
        $repeater = $this->table->getRepeater();

        if ($repeater) {
            $this->setRepeater($repeater);
            return $repeater;
        }

        $repeater = parent::getRepeater();

        return $repeater;
    }

    /**
     * Get the actual table
     *
     * @return MUtil_Html_TableElement
     */
    abstract public function getTable();

    /**
     * is there a repeater source for the lazy data
     *
     * @return boolean
     */
    public function hasRepeater()
    {
        return parent::hasRepeater() || (boolean) $this->table->getRepeater();
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
     * @return MUtil_Model_Bridge_TableBridgeAbstract (continaution pattern)
     * /
    public function setModel(MUtil_Model_ModelAbstract $model)
    {
        $this->model          = $model;
        $this->_displayBridge = $model->getBridgeFor('display');

        return $this;
    } // */

    /**
     * Set the repeater source for the lazy data
     *
     * @param \MUtil_Lazy_RepeatableInterface $repeater
     * @return \MUtil_Model_Format_DisplayFormatter (continuation pattern)
     */
    public function setRepeater(MUtil_Lazy_RepeatableInterface $repeater)
    {
        parent::setRepeater($repeater);

        $this->table->setRepeater($repeater);

        return $this;
    }
}