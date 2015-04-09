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
 * @subpackage Form_Element
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FakeSubmit.php 1280 2013-06-20 16:36:42Z matijsdejong $
 */

/**
 * A button element acting as a Submit button, but possibly placed in the
 * form before the "real" submit button.
 *
 * This ensures that pressing "Enter" will activate the real submit button.
 *
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class MUtil_Bootstrap_Form_Element_FakeSubmit extends \MUtil_Bootstrap_Form_Element_Button
{
    /**
     * Use fakeSubmit view helper by default
     * @var string
     */
    public $helper = 'fakeSubmit';

    public $target;
    public $targetValue;
    public $targetValueIsElement;

    public function getTarget()
    {
        return $this->target;
    }

    public function getTargetValue()
    {
        if (! $this->targetValue) {
            $this->targetValue = $this->getLabel();
        }

        return $this->targetValue;
    }

    public function getTargetValueIsElement()
    {
        return $this->targetValueIsElement;
    }

    public function setTarget($targetName, $value = null, $valueIsOfElement = null)
    {
        $this->target = $targetName;

        if (null !== $value) {
            $this->setTargetValue($value, $valueIsOfElement);
        }

        return $this;
    }

    public function setTargetValue($value, $valueIsOfElement = null)
    {
        $this->targetValue = $value;

        if (null !== $valueIsOfElement) {
            $this->setTargetValueIsElement($valueIsOfElement);
        }

        return $this;
    }

    public function setTargetValueIsElement($valueIsOfElement = false)
    {
        $this->targetValueIsElement = $valueIsOfElement;

        return $this;
    }
}