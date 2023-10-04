<?php

/**
 *
 * @package    Gems
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\JQuery\Form\Element;

/**
 * DatePicker extended with autosubmit
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class DatePicker extends \MUtil\JQuery\Form\Element\DatePicker
{
    /**
     * Load default decorators
     *
     * @return \Zend_Form_Element
     */
    public function loadDefaultDecorators()
    {
        parent::loadDefaultDecorators();
        $this->addDecorator('Description', array('tag' => 'p', 'class' => 'help-block'))
             ->addDecorator('HtmlTag', array(
                 'tag' => 'div',
                 'id'  => array('callback' => array(get_class($this), 'resolveElementId')),
                 'class' => 'element-container'
             ))
             ->addDecorator('Label')
             ->addDecorator('BootstrapRow');
        return $this;
    }

}
