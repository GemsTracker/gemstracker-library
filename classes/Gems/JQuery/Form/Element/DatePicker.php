<?php

/**
 *
 * @package    Gems
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * DatePicker extended with autosubmit
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class Gems_JQuery_Form_Element_DatePicker extends \MUtil_JQuery_Form_Element_DatePicker
        implements \Gems_Form_AutosubmitElementInterface
{
    /**
     * Change the form into an autosubmit form
     *
     * @see \Gems_Form setAutoSubmit
     * @param array $autoSubmitArgs Array containing submitUrl and targetId
     */
    public function enableAutoSubmit(array $autoSubmitArgs)
    {
        $this->setJQueryParam(
                'onSelect',
                new \Zend_Json_Expr('function(dateText, inst) {jQuery(this).trigger(jQuery.Event("keyup"));}')
                );
    }

    /**
     * Load default decorators
     *
     * @return \Zend_Form_Element
     */
    public function loadDefaultDecorators()
    {
        parent::loadDefaultDecorators();
        if (\MUtil_Bootstrap::enabled() === true) {
            $this->addDecorator('Description', array('tag' => 'p', 'class' => 'help-block'))
                 ->addDecorator('HtmlTag', array(
                     'tag' => 'div',
                     'id'  => array('callback' => array(get_class($this), 'resolveElementId')),
                     'class' => 'element-container'
                 ))
                 ->addDecorator('Label')
                 ->addDecorator('BootstrapRow');
        }
        return $this;
    }

}
