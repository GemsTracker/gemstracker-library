<?php
/**
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Takes care of rendering tabs
 *
 * Extension to the baseclass: In this class we merge options and attributes in order to make
 * selecting a tab work the change is in line 73
 *
 * $Id$
 * @filesource
 * @package Gems
 * @subpackage JQuery
 */
class Gems_JQuery_Form_Decorator_TabContainer extends \ZendX_JQuery_Form_Decorator_TabContainer
{
    /**
     * Render an jQuery UI Widget element using its associated view helper
     *
     * Determine view helper from 'helper' option, or, if none set, from
     * the element type. Then call as
     * helper($element->getName(), $element->getValue(), $element->getAttribs())
     *
     * @param  string $content
     * @return string
     * @throws \Zend_Form_Decorator_Exception if element or view are not registered
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $jQueryParams = $this->getJQueryParams();

        //Combine element attribs and decorator options!!
        $attribs     = array_merge($this->getAttribs(), $this->getOptions());

        $helper      = $this->getHelper();
        $id          = $element->getId() . '-container';

        return $view->$helper($id, $jQueryParams, $attribs);
    }

}