<?php

/**
 *
 * @package    Gems
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display a form in a table decorator.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Form_Decorator_Tabs extends \Zend_Form_Decorator_ViewHelper
{
    protected $_cellDecorators;
    protected $_options;
    protected $_subform;

    /**
     * Constructor
     *
     * Accept options during initialization.
     *
     * @param  array|\Zend_Config $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof \Zend_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        } else {
            $this->setOptions(array());
        }
    }

    private function applyDecorators(\Zend_Form_Element $element, array $decorators)
    {
        $element->clearDecorators();
        foreach ($decorators as $decorator) {
            call_user_func_array(array($element, 'addDecorator'), $decorator);
        }

        return $this;
    }

    public function getCellDecorators()
    {
        if (! $this->_cellDecorators) {
            $this->loadDefaultCellDecorators();
        }

        return $this->_cellDecorators;
    }

    /**
     * Retrieve current element
     *
     * @return mixed
     */
    public function getElement()
    {
        return $this->_subform;
    }

    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }
    }

    /**
     * Retrieve decorator options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    public function loadDefaultCellDecorators()
    {
        if (! $this->_cellDecorators) {
            /* $this->_cellDecorators = array(
                array('ViewHelper'),
                array('Errors'),
                array('Description', array('tag' => 'p', 'class' => 'description'))
                ); */
            $this->_cellDecorators = array('ViewHelper', 'Errors');
        }
        return $this->_cellDecorators;
    }

    /**
     * Delete a single option
     *
     * @param  string $key
     * @return bool
     */
    public function removeOption($key)
    {
        unset($this->_options[$key]);
    }

    /**
     * Render the element
     *
     * @param  string $content Content to decorate
     * @return string
     */
    public function render($content)
    {
        $useBootstrap = \MUtil_Bootstrap::enabled();

        if ((null === ($element = $this->getElement())) ||
            (null === ($view = $element->getView()))) {
            return $content;
        }

        $cellDecorators = $this->getCellDecorators();

        $containerDiv = \MUtil_Html::create()->div(array('id' => 'tabElement'));


        if ($element instanceof \MUtil_Form_Element_Table) {
            $containerDiv->appendAttrib('class', $element->getAttrib('class'));
            $subforms = $element->getSubForms();
        } elseif ($element instanceof \Zend_Form)  {
            $cellDecorators = null;
            $subforms = array($element);
        }
        
        if ($subforms) {
            $activeTabs = false;
            if (count($subforms) > 1) {
                $activeTabs = true;
                if (!$useBootstrap) {
                    $jquery = $view->jQuery();

                    $js = sprintf('%1$s("#tabElement").tabs();', \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler());

                    if ($selectedTabElement = $this->getOption('selectedTabElement')) {
                        $js .= sprintf('%1$s("#tabElement").on("tabsactivate", function(event, ui) { console.log(ui.newTab.text()); %1$s("#%2$s").val(ui.newTab.text()) });', \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(), $selectedTabElement);    
                    }
                    
                    $jquery->addOnLoad($js);
                } else {
                    $jquery = $view->jQuery();
                    
                    if ($selectedTabElement = $this->getOption('selectedTabElement')) {
                        $js = sprintf('%1$s(\'a[data-toggle="tab"]\').on(\'shown.bs.tab\', function (e) {
                            var tabtext = $(e.target).text();
                            %1$s("#%2$s").val(tabtext);
                    })', \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(), $selectedTabElement);
                        
                    $jquery->addOnLoad($js);
                    }
                }                   

                $list = $containerDiv->ul(array('class' => 'nav nav-tabs', 'role' => 'tablist'));
            }
            $tabNumber = 0;

            $tabContainerDiv = $containerDiv->div(array('class' => 'tab-content'));//\MUtil_Html::create()->div(array('class' => 'tab-content'));

            $active = $this->getOption('active');
            foreach($subforms as $subform) {
                if ($activeTabs) {
                    if ($tabcolumn = $this->getOption('tabcolumn')) {
                        $tabName = $subform->getElement($tabcolumn)->getValue();
                    } else {
                        $elements = $subform->getElements();
                        $element = reset($elements);
                        $tabName = $element->getValue();
                    }
                    $tabId = $tabName.'-tab';
                    
                    $liOptions = array();
                    $tabPaneOptions = array('id' => $tabId,'class' => 'tab-pane');
                    if ($active && $active == $tabName) {
                        // If a tab is active, select it
                        if (!$useBootstrap) {
                            $js = sprintf('%1$s("#tabElement").tabs({ selected: %2$d});', \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(), $tabNumber);
                            $jquery->addOnLoad($js);
                        } else {
                            $js = sprintf('%1$s(\'a[data-toggle="tab"]\').eq(%2$d).tab(\'show\');', \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(), $tabNumber);
                            $jquery->addOnLoad($js);
                        }
                            
                        $liOptions['class'] = 'active';
                        $tabPaneOptions['class'] .= ' active';
                    }
                    $tabNumber++;

                    $list->li($liOptions)->a('#'.$tabId, $tabName, array('role' => 'tab', 'data-toggle' => 'tab'));
                    if ($useBootstrap) {
                        $subContainer = $tabContainerDiv->div($tabPaneOptions);
                    } else {
                        $subContainer = $tabContainerDiv->div($tabPaneOptions)->table(array('class' => 'formTable'));
                    }
                } else {
                    if($useBootstrap) {
                        $subContainer = $tabContainerDiv;
                    } else {
                        $subContainer = $tabContainerDiv->table(array('class' => 'formTable'));
                    }
                }
                foreach ($subform->getElements() as $subelement) {

                    if ($subelement instanceof \Zend_Form_Element_Hidden) {
                        $this->applyDecorators($subelement, array(array('ViewHelper')));
                        $subContainer[] = $subelement;
                    } else {
                        if ($useBootstrap) {
                            $subgroup = $subContainer->div(array('class' => 'form-group'));

                            $label = $subgroup->div(array('class' => 'label-container'))->label(array('for' => $subelement->getId()));
                            $label[] = $subelement->getLabel();

                            $divContainer = $subgroup->div(array('class' => 'element-container'));

                            $divContainer[] = $subelement;
                        } else {
                            $row = $subContainer->tr();
                            $label = $row->td()->label(array('for' => $subelement->getId()));

                            $label[] = $subelement->getLabel();

                            $column = $row->td();
                            $column[] = $subelement;
                        }    
                        
                    }
                }
            }
        }


        $containerDiv->view = $view;
        $html = $containerDiv;

        return $html;
    }

    /**
     * Set decorator options from a config object
     *
     * @param  \Zend_Config $config
     * @return \Zend_Form_Decorator_Interface
     */
    public function setConfig(\Zend_Config $config)
    {
        $this->setOptions($config->toArray());

        return $this;
    }

    /**
     * Set an element to decorate
     *
     * While the name is "setElement", a form decorator could decorate either
     * an element or a form object.
     *
     * @param  mixed $element
     * @return \Zend_Form_Decorator_Interface
     */
    public function setElement($element)
    {        $this->_subform = $element;

        return $this;
    }


    /**
     * Set a single option
     *
     * @param  string $key
     * @param  mixed $value
     * @return \Zend_Form_Decorator_Interface
     */
    public function setOption($key, $value)
    {
        switch ($key) {
            case 'cellDecorator':
                $value = $this->getCellDecorators() + array($value);

            case 'cellDecorators':
                $this->_cellDecorators = $value;
                break;

            default:
                $this->_options[$key] = $value;
                break;
        }

        return $this;
    }

    /**
     * Set decorator options from an array
     *
     * @param  array $options
     * @return \Zend_Form_Decorator_Interface
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key,  $value);
        }

        return $this;
    }
}