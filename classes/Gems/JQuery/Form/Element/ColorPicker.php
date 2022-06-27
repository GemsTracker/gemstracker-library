<?php

/**
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ColorPicker.php Jasper van Gestel $
 */

/**
 *
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5
 */
class Gems_JQuery_Form_Element_ColorPicker extends \ZendX_JQuery_Form_Element_ColorPicker
{
    /**
     * Constructor
     *
     * $spec may be:
     * - string: name of element
     * - array: options with which to configure element
     * - \Zend_Config: \Zend_Config with options for configuring element
     *
     * @param  string|array|\Zend_Config $spec
     * @param  array|\Zend_Config $options
     * @return void
     * @throws \Zend_Form_Exception if no element name after initialization
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);

        $this->setAttrib('label_class', 'radio-inline');
    }

	/**
     * Load default decorators
     *
     * @return \Zend_Form_Element
     */
    public function loadDefaultDecorators()
    {
        $this->addDecorator('UiWidgetElement')
             ->addDecorator('Errors')
             ->addDecorator('Description', array('tag' => 'p', 'class' => 'help-block'))
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