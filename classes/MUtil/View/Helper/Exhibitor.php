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
 * @subpackage View
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage View
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_View_Helper_Exhibitor extends Zend_View_Helper_FormElement
{
    /**
     * Generates a fake element that just displays the item with a hidden extra value field.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are extracted in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function exhibitor($name, $value = null, $attribs = null)
    {
        $result = $value;
        MUtil_Echo::track($result);

        if (isset($attribs['default'])) {
            if (null === $result) {
                $result = $attribs['default'];
            }
        }

        if (isset($attribs['multiOptions'])) {
            $multiOptions = $attribs['multiOptions'];

            if (is_array($multiOptions)) {
                /*
                 *  Sometimes a field is an array and will be formatted later on using the
                 *  formatFunction -> handle each element in the array.
                 */
                if (is_array($result)) {
                    foreach($result as $key => $arrayValue) {
                        if (array_key_exists($arrayValue, $multiOptions)) {
                            $result[$key] = $multiOptions[$arrayValue];
                        }
                    }
                } else {
                    if (array_key_exists($result, $multiOptions)) {
                        $result = $multiOptions[$result];
                    }
                }
            }
        }

        if (isset($attribs['formatFunction'])) {
            $callback = $attribs['formatFunction'];
            $result = call_user_func($callback, $result);
        } elseif (isset($attribs['dateFormat'])) {
            $dateFormat    = $attribs['dateFormat'];
            $storageFormat = isset($attribs['storageFormat']) ? $attribs['storageFormat'] : null;

            $result = MUtil_Date::format($result, $dateFormat, $storageFormat);

            if ($value instanceof Zend_Date) {
                $value = $value->toString($dateFormat);
            }
        }

        if (isset($attribs['itemDisplay'])) {
            $function = $attribs['itemDisplay'];

            if (is_callable($function)) {
                $result = call_user_func($function, $result);

            } elseif (is_object($function)) {

                if (($function instanceof MUtil_Html_ElementInterface)
                    || method_exists($function, 'append')) {

                    $object = clone $function;

                    $result = $object->append($result);
                }

            } elseif (is_string($function)) {
                // Assume it is a html tag when a string

                $result = MUtil_Html::create($function, $result);
            }
        }

        if ($result instanceof MUtil_Html_HtmlInterface) {
            $result = $result->render($this->view);
        }

        // By all appearance not in use.
        /* if (isset($attribs['callback'])) {
            $callback = $attribs['callback'];
            $result = $callback($result);
        } */

        if (isset($attribs['nohidden']) && $attribs['nohidden'] || is_array($value)) {
            return $result;
        } else {
            if ($value instanceof Zend_Date) {
                $value = $value->toString(Zend_Date::ISO_8601);
            }
            return $this->_hidden($name, $value) . $result;
        }
    }
}
