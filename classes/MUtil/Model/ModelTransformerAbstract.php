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
abstract class MUtil_Model_ModelTransformerAbstract extends MUtil_Model_ModelAbstract
{
    protected $sourceModel;

    public function __construct(array $args, array $paramTypes = array())
    {
        $paramTypes['sourceModel'] = 'MUtil_Model_ModelAbstract';
        $paramTypes['name']        = 'is_string';

        $args = MUtil_Ra::args($args, $paramTypes); 

        if (isset($args['name'])) {
            $name = $args['name'];
            unset($args['name']);
        } else {
            if (isset($args['sourceModel'])) {
                $name = $args['sourceModel']->getName();
            } else {
                // MUtil_Echo::r($args);
                throw new MUtil_Model_ModelException('No $name or $sourceModel parameter specified for ' . get_class($this) . ' constructor.');
            }
        }
        // MUtil_Echo::r($args, $name);

        parent::__construct($name);

        foreach ($args as $name => $arg) {
            $function = 'set' . ucfirst($name);
            if (method_exists($this, $function)) {
                $this->$function($arg);
            } else {
                throw new MUtil_Model_ModelException("Unknown argument $name in " . get_class($this) . ' constructor.');
            }
        }
    }

    protected function _getKeyValue($name, $key)
    {
        if ($this->sourceModel) {
            return $this->sourceModel->_getKeyValue($name, $key);
        }
    }

    public function delete($filter = true)
    {
        throw new Exception('Cannot delete ' . get_class($this) . ' data.');
    }

    public function get($name, $arrayOrKey1 = null, $key2 = null)
    {
        if ($this->sourceModel) {
            $args = func_get_args();

            call_user_func_array(array($this->sourceModel, 'get'), $args);
        }
        return $this;
    }

    public function getAlias($name)
    {
        if ($this->sourceModel) {
            return $this->sourceModel->getAlias($name);
        }
    }

    public function getItemNames()
    {
        if ($this->sourceModel) {
            return $this->sourceModel->getItemNames();
        }
    }

    public function getItemsOrdered()
    {
        if ($this->sourceModel) {
            return $this->sourceModel->getItemsOrdered();
        }
    }

    public function getKeyRef($forData, $href = array())
    {
        if ($this->sourceModel) {
            return $this->sourceModel->getKeyRef($forData, $href);
        }
    }

    public function getKeys($reset = false)
    {
        if ($this->sourceModel) {
            return $this->sourceModel->getKeys($reset);
        }
    }

    public function getMeta($key, $default = null)
    {
        if ($this->sourceModel) {
            return $this->sourceModel->getMeta($key, $default);
        }
    }

    public function getSourceModel() 
    {
        return $this->sourceModel;
    }

    public function has($name, $subkey = null)
    {
        if ($this->sourceModel) {
            return $this->sourceModel->has($name, $subkey);
        }
        return false;
    }

    public function hasMeta($key)
    {
        if ($this->sourceModel) {
            return $this->sourceModel->hasMeta($key);
        }
        return false;
    }

    public function hasNew()
    {
        return false;
    }

    public function load($filter = true, $sort = true)
    {
        $data = $this->sourceModel->load($filter, $sort);

        return $this->transform($data, $filter, $sort);
    }

    public function resetOrder()
    {
        if ($this->sourceModel) {
            $this->sourceModel->resetOrder();
        }
        return $this;
    }

    public function save(array $newValues, array $filter = null)
    {
        throw new Exception('Cannot save ' . get_class($this) . ' data.');
    }

    public function set($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        if ($this->sourceModel) {
            $args = func_get_args();

            call_user_func_array(array($this->sourceModel, 'set'), $args);
        }
        return $this;
    }

    public function setKeys(array $keys)
    {
        if ($this->sourceModel) {
            $this->sourceModel->setKeys($key, $value);
        }
        return $this;
    }

    public function setMeta($key, $value)
    {
        if ($this->sourceModel) {
            $this->sourceModel->setMeta($key, $value);
        }
        return $this;
    }

    public function setSourceModel(MUtil_Model_ModelAbstract $model) 
    {
        $this->sourceModel = $model;
        return $this;
    }

    abstract public function transform($data, $filter = true, $sort = true);
}
