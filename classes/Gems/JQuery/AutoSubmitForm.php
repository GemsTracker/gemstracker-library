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
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_JQuery_AutoSubmitForm extends \Gems_JQuery_JQueryExtenderAbstract
{
    protected $formId = 'auto';
    protected $jqueryParams = array('submitUrl' => null, 'targetId' => null);
    protected $localScriptFiles = '/gems/js/autoSubmitForm.js';
    protected $name = 'autoSubmitForm';

    public function __construct($submitUrl, $targetId, $formId = null, $args = null)
    {
        // Filter out elements passed by type
        $args = \MUtil_Ra::args(func_get_args(),
            array(
                'submitUrl' => array('MUtil_Html_UrlArrayAttribute', 'is_array', 'is_string'),
                'targetId'  => array('MUtil_Html_ElementInterface', 'is_string'),
                'formId'    => array('Zend_Form', 'is_string'),
                ), null, \MUtil_Ra::STRICT);

        // \MUtil_Echo::r($args);

        parent::__construct($args);
    }

    public function getSelector()
    {

        if (null === $this->getSubmitUrl()) {
            throw new \Gems_Exception_Coding('No submitUrl defined for ' . __CLASS__);
        }
        if (null === $this->getTargetId()) {
            throw new \Gems_Exception_Coding('No targetId defined for ' . __CLASS__);
        }

        if ($this->formId) {
            return 'form#' . $this->formId;
        }

        return 'form';
    }


    public function getSubmitUrl()
    {
        return $this->getJQueryParam('submitUrl');
    }

    public function getTargetId()
    {
        return $this->getJQueryParam('targetId');
    }

    public function setFormId($value)
    {
        if ($value instanceof \Zend_Form)
        {
            $value = $value->getId();
        }
        $this->formId = $value;
        return $this;
    }

    public function setSubmitUrl($value)
    {
        if (($this->view instanceof \Zend_View_Abstract) && ($value instanceof \MUtil_Html_HtmlInterface)) {
            $value = $value->render($this->view);
        }

        $this->setJQueryParam('submitUrl', $value);
        return $this;
    }

    public function setTargetId($value)
    {
        if ($value instanceof \MUtil_Html_ElementInterface) {
            $value = isset($value->id) ? '#' . $value->id : (isset($value->class) ? '.' . $value->class: $value->getTagName());
        } else {
            $target = '#' . $value;
        }

        $this->setJQueryParam('targetId', $target);
        return $this;
    }

    public function setView(\Zend_View_Abstract $view)
    {
        $url = $this->getJQueryParam('submitUrl');
        if ($url instanceof \MUtil_Html_HtmlInterface) {
            $this->setJQueryParam('submitUrl', $url->render($view));
        }

        return parent::setView($view);
    }
}

