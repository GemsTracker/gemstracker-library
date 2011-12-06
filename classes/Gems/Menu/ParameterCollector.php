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
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Menu_ParameterCollector
{
    protected $sources = array();
    protected $values = array();

    public function __construct()
    {
        $sources = MUtil_Ra::args(func_get_args());
        $array   = array();
        foreach ($sources as $key => $source) {
            // Fix for array sources.
            if (is_string($key)) {
                $array[$key] = $source;
            } else {
                $this->addSource($source);
            }
        }
        if ($array) {
            $this->addSource($array);
        }
    }

    public function addSource($source)
    {
        array_unshift($this->sources, $source);
    }

    /**
     * Returns a value to use as parameter for $name or
     * $default if this object does not contain the value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getMenuParameter($name, $altname = null)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        $this->values[$name] = null;
        foreach ($this->sources as $source) {
            if ($source instanceof Zend_Controller_Request_Abstract) {
                $value = $source->getParam($name, null);
                if (null === $value) {
                    $value = $source->getParam($altname, $this->values[$name]);
                }
                $this->values[$name] = $value;

            } elseif ($source instanceof Gems_Menu_ParameterSourceInterface) {
                $this->values[$name] = $source->getMenuParameter($name, $this->values[$name]);

            } elseif ($source instanceof MUtil_Lazy_RepeatableInterface) {
                $this->values[$name] = $source->__get($name);

            } elseif (is_array($source)) {
                // MUtil_Echo::track($name, $source);
                if (isset($source[$name])) {
                    $this->values[$name] = $source[$name];
                }
            }
            if (null !== $this->values[$name]) {
                break;
            }
        }
        return $this->values[$name];
    }
}
