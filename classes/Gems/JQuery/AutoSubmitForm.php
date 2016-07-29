<?php

/**
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

