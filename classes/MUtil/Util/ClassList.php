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
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Util
 */

/**
 * 
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Util
 */
class MUtil_Util_ClassList extends MUtil_Util_LookupList
{
    protected $_subClasses;
    protected $_notSubClasses;

    /**
     * Function triggered when the underlying lookup array has changed.
     *
     * This function exists to allow overloading in subclasses.
     *
     * @return void
     */
    protected function _changed()
    {
        // Clean up caches
        $this->_subClasses = array();
        $this->_notSubClasses = array();
    }

    /**
     * Item lookup function.
     *
     * This is a separate function to allow overloading by subclasses.
     *
     * @param scalar $key
     * @param mixed $default
     * @return mixed
     */
    protected function _getItem($key, $default = null)
    {
        if (is_object($key)) {
            $class = get_class($key);

            // Check for existence
            if ($result = parent::_getItem($class, $default)) {
                return $result;
            }
            // Check was already found
            if (array_key_exists($class, $this->_subClasses)) {
                return $this->_subClasses[$class];
            }
            // Check was already searched and not found
            if (array_key_exists($class, $this->_notSubClasses)) {
                return $default;
            }

            // Check the parent classes of the object
            $parents = class_parents($key);
            $result = null;
            foreach ($parents as $parentClass) {
                if ($result = parent::_getItem($parentClass, null)) {
                    // Add the current class to the cache
                    $this->_subClasses[$class] = $result;

                    // Add all parents up to the one matching to the cache
                    foreach ($parents as $priorParent) {
                        $this->_subClasses[$priorParent] = $result;
                        if ($parentClass === $priorParent) {
                            // Further parents are not automatically in the list
                            break;
                        }
                    }
                    return $result;
                }
            }

            // Check the interfaces implemented by the object
            $implemented = class_implements($key);
            foreach ($implemented as $interface) {
                if ($result = parent::_getItem($interface, null)) {
                    //    Add the current class to the cache
                    $this->_subClasses[$class] = $result;
                    return $result;
                }
            }

            // Add to the not found cache
            $this->_notSubClasses[$class] = true;

            return $default;
        } else {
            return parent::_getItem($key, $default);
        }
    }
}