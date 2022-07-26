<?php
/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\JQuery\Form\Element;

/**
 * Short description for ToggleCheckboxes
 *
 * Long description for class ToggleCheckboxes (if any)...
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ToggleCheckboxes extends \Zend_Form_Element_Button
{
    /**
     * Use toggleCheckboxes view helper by default
     * @var string
     */
    public $helper = 'toggleCheckboxes';

    /**
     * Create a button to toggle all cyhackboxes found by a given jQuery selector
     *
     * Specify the 'selector' in the options http://api.jquery.com/category/selectors/
     *
     * Usage:
     * $element = new \Gems\JQuery\Form\Element\ToggleCheckboxes('name', array('selector'=>'input[name^=oid]')
     *
     * @param type $spec
     * @param type $options
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);
    }

    /**
     * Set the view object
     *
     * Ensures that the view object has the \Gems_jQuery view helper path set.
     *
     * @param  \Zend_View_Interface $view
     * @return \Gems\JQuery\Form\Element\ToggleCheckboxes
     */
    public function setView(\Zend_View_Interface $view = null)
    {
        $element = parent::setView($view);
        if (null !== $view) {
            if (false === $view->getPluginLoader('helper')->getPaths('Gems_JQuery_View_Helper')) {
                $view->addHelperPath('Gems/JQuery/View/Helper', 'Gems_JQuery_View_Helper');
            }
        }
        return $element;
    }
}