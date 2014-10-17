<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.+
 * 
 *
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $id: HtmlFormatter.php 203 2013-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since 2014 $(date} 22:00:02
 */
class MUtil_Model_Bridge_DisplayBridge extends MUtil_Model_Bridge_BridgeAbstract
{
    /**
     * Return an array of functions used to process the value
     *
     * @param string $name The real name and not e.g. the key id
     * @return array
     */
    protected function _compile($name)
    {
        $output = array();

        if ($this->model->has($name, 'multiOptions')) {
            $options = $this->model->get($name, 'multiOptions');

            $output['multiOptions'] = function ($value) use ($options) {
                return is_scalar($value) && array_key_exists($value, $options) ? $options[$value] : $value;
            };
        }

        if ($this->model->has($name, 'formatFunction')) {
            $output['formatFunction'] = $this->model->get($name, 'formatFunction');

        } elseif ($this->model->has($name, 'dateFormat')) {
            $format = $this->model->get($name, 'dateFormat');
            if (is_callable($format)) {
                $output['dateFormat'] = $format;
            } else {
                $storageFormat = $this->model->get($name, 'storageFormat');
                $output['dateFormat'] = function ($value) use ($format, $storageFormat) {
                    return MUtil_Date::format($value, $format, $storageFormat);
                };
            }
        }

        if ($this->model->has($name, 'markCallback')) {
            $output['markCallback'] = $this->model->get($name, 'markCallback');
        }

        return $output;
    }
}
