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
 * @package    MUtil
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Render output for a view.
 *
 * This object handles \MUtil_Html_HtmlInterface and \MUtil_Lazy_LazyInterface
 * objects natively, as well as array, scalar values and objects with a
 * __toString function.
 *
 * All other object types passed to the renderer should have a render function
 * defined for them in the ClassRenderList.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_Renderer
{
    /**
     *
     * @var \MUtil_Util_ClassList
     */
    protected $_classRenderFunctions;

    /**
     * Default array of objects rendering functions.
     *
     * The \MUtil_Html_Renderer::doNotRender function allows items to be passed
     * as content without triggering error messages.
     *
     * This is usefull if you want to pass an item to sub objects, but are not
     * sure that it will be used in every case.
     *
     * @var array classname => static output function
     */
    protected $_initialClassRenderFunctions = array(
        'Zend_Db_Adapter_Abstract'         => 'MUtil_Html_Renderer::doNotRender',
        'Zend_Controller_Request_Abstract' => 'MUtil_Html_Renderer::doNotRender',
        'Zend_Form'                        => 'MUtil_Html_InputRenderer::renderForm',
        'Zend_Form_DisplayGroup'           => 'MUtil_Html_InputRenderer::renderDisplayGroup',
        'Zend_Form_Element'                => 'MUtil_Html_InputRenderer::renderElement',
        'Zend_Translate'                   => 'MUtil_Html_Renderer::doNotRender',
    );

    /**
     * Create the renderer
     *
     * @param mixed $classRenderFunctions Array of classname => renderFunction or \MUtil_Util_ClassList
     * @param boolean $append Replace when false, append to default definitions otherwise
     */
    public function __construct($classRenderFunctions = null, $append = true)
    {
        $this->setClassRenderList($classRenderFunctions, $append);
    }

    /**
     * Check if the value can be rendered by this object
     *
     * @param mixed $value
     * @return boolean True when the object can be rendered
     */
    public function canRender($value)
    {
        if (is_object($value)) {
            if (($value instanceof \MUtil_Lazy_LazyInterface) ||
                ($value instanceof \MUtil_Html_HtmlInterface) ||
                method_exists($value, '__toString')) {
                return true;
            }

            return $this->_classRenderFunctions->get($value);

        }  else {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    if (! $this->canRender($val)) {
                        return false;
                    }
                }
            }

            return true;
        }
    }

    /**
     * Static helper function used for object types that should
     * not produce output when (accidently) rendered.
     *
     * Eg \Zend_Translate or \Zend_Db_Adapter_Abstract
     *
     * @param \Zend_View_Abstract $view
     * @param mixed $content
     * @return null
     */
    public static function doNotRender(\Zend_View_Abstract $view, $content)
    {
        if (\MUtil_Html::$verbose) {
            \MUtil_Echo::r('Did not render ' . get_class($content) . ' object.');
        }
        return null;
    }

    /**
     * Get the classlist containing render functions for non-builtin objects
     *
     * @return \MUtil_Util_ClassList
     */
    public function getClassRenderList()
    {
        return $this->_classRenderFunctions;
    }

    /**
     * Renders the $content so that it can be used as output for the $view,
     * including output escaping and encoding correction.
     *
     * This functions handles \MUtil_Html_HtmlInterface and \MUtil_Lazy_LazyInterface
     * objects natively, as well as array, scalar values and objects with a
     * __toString function.
     *
     * Other objects a definition should have a render function in getClassRenderList().
     *
     * All Lazy variabables are raised.
     *
     * @param \Zend_View_Abstract $view
     * @param mixed $content Anything HtmlInterface, number, string, array, object with __toString
     *                      or an object that has a defined render function in getClassRenderList().
     * @return string Output to echo to the user
     */
    public function renderAny(\Zend_View_Abstract $view, $content)
    {
        // Resolve first as this function as recursion heavy enough as it is.
        if ($content instanceof \MUtil_Lazy_LazyInterface) {
            $content = \MUtil_Lazy::rise($content);
        }

        if ($content) {
            if ($content instanceof \MUtil_Html_HtmlInterface) {
                $new_content = $content->render($view);

            } elseif (is_array($content) && (! is_object($content))) {

                // Again, skip on the recursion count
                foreach ($content as $key => $item) {
                    $new_content[] = $this->renderAny($view, $item);
                }

                return implode('', $new_content);

            } else {
                if (is_object($content)) {
                    if ($function = $this->_classRenderFunctions->get($content)) {
                        return call_user_func($function, $view, $content);
                    }

                    if (method_exists($content, '__toString')) {
                        $new_content = $content->__toString();
                    } else {
                        // $new_content = 'WARNING: Object of type ' . get_class($content) . ' cannot be converted to string.';
                        throw new \MUtil_Html_HtmlException('WARNING: Object of type ' . get_class($content) . ' cannot be converted to string.');
                    }
                    
                } elseif ($content instanceof __PHP_Incomplete_Class) {
                    \MUtil_Echo::r($content, __CLASS__ . '->' .  __FUNCTION__);
                    return '';

                } else {
                    $new_content = (string) $content;
                }

                $new_content = $view->escape($new_content);
            }

            return $new_content;

        }

        if (! is_array($content)) { // Skip empty array
            return $content;  // Returns 0 (zero) and '' when that is the value of $content
        }
    }

    /**
     * Change the list of non-builtin objects that can be rendered by this renderer.
     *
     * @param mixed $classRenderFunctions Array of classname => renderFunction or \MUtil_Util_ClassList
     * @param boolean $append Replace when false, append otherwise
     * @return \MUtil_Html_Renderer (continuation pattern)
     */
    public function setClassRenderList($classRenderFunctions = null, $append = false)
    {
        if ($classRenderFunctions instanceof \MUtil_Util_ClassList) {
            $this->_classRenderFunctions = $classRenderFunctions;
        } else {
            $this->_classRenderFunctions = new \MUtil_Util_ClassList($this->_initialClassRenderFunctions);

            if ($classRenderFunctions) {
                if ($append) {
                    $this->_classRenderFunctions->add((array) $classRenderFunctions);
                } else {
                    $this->_classRenderFunctions->set((array) $classRenderFunctions);
                }
            }
        }
        return $this;
    }
}
