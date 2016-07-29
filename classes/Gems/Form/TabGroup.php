<?php

/**

/**
 * A special displaygroup, to be displayed in a jQuery tab. Main difference is in the decorators.
 *
 * @version $Id$
 * @author 175780
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class Gems_Form_TabGroup extends \Zend_Form_DisplayGroup {

    private $_alternate = null;

    public function  __construct($name, \Zend_Loader_PluginLoader $loader, $options = null) {
        $this->_alternate = new \MUtil_Lazy_Alternate(array('odd','even'));
        parent::__construct($name, $loader, $options);
    }

    /**
     * Add element to stack
     *
     * @param  \Zend_Form_Element $element
     * @return \Zend_Form_DisplayGroup
     */
    public function addElement(\Zend_Form_Element $element)
    {
        $decorators = $element->getDecorators();
        $decorator = array_shift($decorators);
        $element->setDecorators(array($decorator,
            array('Description', array('class'=>'description')),
                            'Errors',
                            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
                            array('Label'),
                            array(array('labelCell' => 'HtmlTag'), array('tag' => 'td', 'class'=>'label')),
                            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'class' => $this->_alternate))
            ));
        
        return parent::addElement($element);
    }

    /**
     * Load default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements')
                 ->addDecorator(array('table' => 'HtmlTag'), array('tag' => 'table', 'class'=>'formTable'))
                 ->addDecorator(array('tab' => 'HtmlTag'), array('tag' => 'div', 'class' => 'displayGroup'))
                 ->addDecorator('TabPane', array('jQueryParams' => array('containerId' => 'mainForm',
                                                                         'title' => $this->getAttrib('title'))));
        }
        return $this;
    }
}