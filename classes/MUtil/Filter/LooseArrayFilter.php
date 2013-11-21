<?php

/**
 * Copyright (c) 201e, Erasmus MC
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
 * @subpackage Filter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $Id: LooseArrayFilter.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Filter
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Filter_LooseArrayFilter implements Zend_Filter_Interface
{
    /**
     *
     * @var uppercase translate value => actual value
     */
    private $_basicValues = array();

    /**
     *
     * @var uppercase array translate value => actual value
     */
    private $_extraValues = array();

    /**
     *
     * @param array $options key => label
     * @param array $extraValues extra key value => actual value
     */
    public function __construct(array $options, array $extraValues = null)
    {
        $this->setMultiOptions($options);

        if (is_array($extraValues)) {
            $this->setExtraTranslations($extraValues);
        }
    }

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        $test = strtoupper($value);

        if (array_key_exists($test, $this->_basicValues)) {
            return $this->_basicValues[$test];
        }
        if (array_key_exists($test, $this->_extraValues)) {
            return $this->_extraValues[$test];
        }

        return $value;
    }

    /**
     * Set any extra tranlations, e.g. V => F or MO => Monday
     *
     * @param array $options extra  key value => actual value
     * @return \MUtil_Filter_LooseArrayFilter (Continuation pattern)
     */
    public function setExtraTranslations(array $options)
    {
        $this->_extraValues = array_combine(array_map('strtoupper', array_keys($options)), $options);

        return $this;
    }

    /**
     * The basic options of the element
     *
     * @param array $options key => label
     * @return \MUtil_Filter_LooseArrayFilter (Continuation pattern)
     */
    public function setMultiOptions(array $options)
    {
        $keys = array_keys($options);
        $this->_basicValues =
                array_combine(array_map('strtoupper', $keys), $keys) +
                array_combine(array_map('strtoupper', $options), $keys);

        return $this;
    }

}
