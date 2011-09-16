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
 * @package    Gems
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Format and araay of data according to a provided model
 *
 * @package    Gems
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_FormattedData extends IteratorIterator
{
    /**
     * @var MUtil_Model_ModelAbstract
     */
    private $model;

    /**
     * @var ArrayObject
     */
    private $data;

    private $formatted;

    public function __construct($data, MUtil_Model_ModelAbstract $model, $formatted = true) {
        $this->data  = parent::__construct(new ArrayObject($data));
        $this->model = $model;
        $this->setFormatted($formatted);
        return $this;
    }

    public function current() {
        //Here we get the actual record to transform!
        $row = parent::current();
        if ($this->formatted) {
            $row = $this->format($row, $this->model);
        }
        return $row;
    }

    /**
     * Formats a row of data using the given model
     *
     * Static method only available for single rows, for a convenient way of using on a
     * rowset, use the class and iterate
     *
     * @param array $row
     * @param MUtil_Model_ModelAbstract $model
     * @return array The formatted array
     */
    static function format($row, $model) {
        foreach ($row as $fieldname=>$value) {
                $row[$fieldname] = self::_format($fieldname, $row[$fieldname], $model);
        }
        return $row;
    }

    /**
     * This is the actual format function, copied from the Exhibitor for field
     *
     * @param type $name
     * @param type $result
     * @return type
     */
    private static function _format($name, $result, $model)
    {
        if ($default = $model->get($name,'default')) {
            if (null === $result) {
                $result = $default;
            }
        }

        if ($multiOptions = $model->get($name, 'multiOptions')) {
            if (is_array($multiOptions)) {
                if (array_key_exists($result, $multiOptions)) {
                    $result = $multiOptions[$result];
                }
            }
        }

        if ($dateFormat    = $model->get($name, 'dateFormat')) {
            $storageFormat = $model->get($name, 'storageFormat');
            $result = MUtil_Date::format($result, $dateFormat, $storageFormat);
        }

        if ($callback = $model->get($name, 'formatFunction')) {
            $result = call_user_func($callback, $result);
        }

        if ($function = $model->get($name, 'itemDisplay')) {
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
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $view = $viewRenderer->view;
            $result = $result->render($view);
        }
        return $result;
     }

     public function setFormatted($bool) {
         $this->formatted = $bool;
     }
}