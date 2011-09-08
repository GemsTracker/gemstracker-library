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
 * @subpackage Html
 */

class MUtil_Html_Renderer
{
    protected $_classRenderFunctions;

    // The MUtil_Html_Renderer::doNotRender items allow you to pass these
    // items to a content without triggering error messages.
    //
    // This is usefull if you want to pass an item but are not sure that 
    // it will be used in very case.
    protected $_initialClassRenderFunctions = array(
        'Zend_Db_Adapter_Abstract'         => 'MUtil_Html_Renderer::doNotRender',
        'Zend_Controller_Request_Abstract' => 'MUtil_Html_Renderer::doNotRender',
        'Zend_Form'                        => 'MUtil_Html_InputRenderer::renderForm',
        'Zend_Form_DisplayGroup'           => 'MUtil_Html_InputRenderer::renderDisplayGroup',
        'Zend_Form_Element'                => 'MUtil_Html_InputRenderer::renderElement',
        'Zend_Translate'                   => 'MUtil_Html_Renderer::doNotRender',
    );

    public function __construct($classRenderFunctions = null, $append = true)
    {
        $this->setClassRenderList($classRenderFunctions, $append);
    }

    public function canRender($value)
    {
        if (is_object($value)) {
            if (method_exists($value, '__toString') ||
                ($value instanceof MUtil_Lazy_LazyInterface) ||
                ($value instanceof MUtil_Html_HtmlInterface)) {
                return true;
            }

            return $this->_classRenderFunctions->get($content);

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

    public static function doNotRender(Zend_View_Abstract $view, $content)
    {
        if (MUtil_Html::$verbose) {
            MUtil_Echo::r('Did not render ' . get_class($content) . ' object.');
        }
        return null;
    }

    public function getClassRenderList()
    {
        return $this->_classRenderFunctions;
    }

    public function renderAny(Zend_View_Abstract $view, $content)
    {
        // Resolve first as this function as recursion heavy enough as it is.
        if ($content instanceof MUtil_Lazy_LazyInterface) {
            $content = MUtil_Lazy::rise($content);
        }

        if ($content) {
            if ($content instanceof MUtil_Html_HtmlInterface) {
                $new_content = $content->render($view);

            } elseif (is_array($content) && (! is_object($content))) {

                // Again, skip on the recursion count
                foreach ($content as $key => $item) {
                    $new_content[$key] = $this->renderAny($view, $item);
                    // MUtil_Echo::r($key . '=>' . $new_content[$key]);
                    if (null === $new_content[$key]) {
                        unset($new_content[$key]);
                    }
                }

                if (count($new_content)) {
                    $new_content = implode('', $new_content);
                } else {
                    return null;
                }

            } else {
                if (is_object($content)) {
                    if ($function = $this->_classRenderFunctions->get($content)) {
                        return call_user_func($function, $view, $content);
                    }

                    if (method_exists($content, '__toString')) {
                        $new_content = $content->__toString();
                    } else {
                        // $new_content = 'WARNING: Object of type ' . get_class($content) . ' cannot be converted to string.';
                        throw new MUtil_Html_HtmlException('WARNING: Object of type ' . get_class($content) . ' cannot be converted to string.');
                    }
                } else {
                    $new_content = (string) $content;
                }

                $new_content = $view->escape($new_content);
            }

            return $new_content;

        } elseif (! is_array($content)) {
            return $content;  // Returns 0 (zero) and '' when that is the value of $content
        }
    }

    public function renderArray(Zend_View_Abstract $view, array $content)
    {
        if ($content) {
            foreach ($content as $key => $item) {
                $content[$key] = $this->renderAny($view, $item);
                if (null === $content[$key]) {
                    unset($content[$key]);
                }
            }

            return $content;
        }
    }

    public function setClassRenderList($classRenderFunctions = null, $append = false)
    {
        if ($classRenderFunctions instanceof MUtil_Util_ClassList) {
            $this->_classRenderFunctions = $classRenderFunctions;
        } else {
            $this->_classRenderFunctions = new MUtil_Util_ClassList($this->_initialClassRenderFunctions);

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
