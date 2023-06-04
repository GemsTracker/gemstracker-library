<?php
/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Form\Decorator;

/**
 * Render a tooltip as a jQuery tooltip
 *
 * $Id$
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class Tooltip extends \Zend_Form_Decorator_Abstract
{
    /**
     * Whether or not to escape the description
     * Default: false
     * @var bool
     */
    protected $_escape = false;

    /**
     * View helper
     * @var string
     */
    protected $_helper;

    /**
     * Default placement: append
     * @var string
     */
    protected $_placement = 'PREPEND';

    /**
     * HTML tag with which to surround description
     * @var string
     */
    protected $_tag;

    /**
     * Get class with which to define description
     *
     * Defaults to 'tooltip'
     *
     * @return string
     */
    public function getClass()
    {
        $class = $this->getOption('class');
        if (null === $class) {
            $class = 'tooltip';
            $this->setOption('class', $class);
        }

        return $class;
    }

    /**
     * Get view helper for rendering container
     *
     * @return string
     */
    public function getHelper()
    {
        if (null === $this->_helper) {
            require_once 'Zend/Form/Decorator/Exception.php';
            throw new \Zend_Form_Decorator_Exception('No view helper specified for Tooltip decorator');
        }
        return $this->_helper;
    }

    public function getImg()
    {
        return '<div class="ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all">
   <span class="ui-icon ui-icon-help"></span>
</div>';
        //return '<img src="' . \Zend_Controller_Front::getInstance()->getBaseUrl() . '/gems/js/images/question_mark.gif">';
    }

    /**
     * Get HTML tag, if any, with which to surround tooltip
     *
     * @return string
     */
    public function getTag()
    {
        if (null === $this->_tag) {
            $tag = $this->getOption('tag');
            if (null !== $tag) {
                $this->removeOption('tag');
            } else {
                $tag = 'span';
            }

            $this->setTag($tag);
            return $tag;
        }

        return $this->_tag;
    }

    /**
     * Get escape flag
     *
     * @return true
     */
    public function getEscape()
    {
        if (null === $this->_escape) {
            if (null !== ($escape = $this->getOption('escape'))) {
                $this->setEscape($escape);
                $this->removeOption('escape');
            } else {
                $this->setEscape(true);
            }
        }

        return $this->_escape;
    }

    /**
     * Render a tooltip
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $tooltip = $element->getAttrib('tooltip');
        if (is_string($tooltip)) {
            $tooltip = trim($tooltip);
        }
        //$element->removeAttrib('tooltip');

        if (empty($tooltip)) {
            return $content;
        }

        $view->headScript()->appendFile(\Zend_Controller_Front::getInstance()->getBaseUrl()  .  '/gems/js/jquery.cluetip.js');
        $view->headLink()->appendStylesheet(\Zend_Controller_Front::getInstance()->getBaseUrl()  . '/gems/js/jquery.cluetip.css');
        $script = "$('" . $this->getTag() . ".tooltip').cluetip({
            activation: 'click',
            sticky: 'true',
            closeText: '[X]',
            closePosition:    'title',
            width: 450,
            titleAttribute: 'tip',
            splitTitle: '|'})
                   $('#tabContainer').bind( 'tabsselect', function(event, ui) {
  $(document).trigger('hideCluetip');
});";

        $view->jQuery()->addOnLoad($script);

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $class     = $this->getClass();
        $tag       = $this->getTag();
        $escape    = $this->getEscape();

        $options   = $this->getOptions();

        if ($escape) {
            $tooltip = $view->escape($tooltip);
        }

        $options['tag'] = $tag;
        $options['tip'] = str_replace('"', '\"', $tooltip);
        $decorator = new \Zend_Form_Decorator_HtmlTag($options);
        $tooltip = $decorator->render($this->getImg()) . $decorator->setOptions(array('class'=>'thetooltip', 'id'=>'tooltip-' . $this->getElement()->getAttrib('id')))->render($tooltip);

        switch ($placement) {
            case self::PREPEND:
                return $tooltip . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $tooltip;
        }

        /* Misschien later toch toevoegen */
        $helper = $this->getHelper();

        return $view->$helper($id, $content, $attribs);
    }

    /**
     * Set whether or not to escape tooltip
     *
     * @param  bool $flag
     * @return \Gems\Form\Decorator\Tooltip
     */
    public function setEscape($flag)
    {
        $this->_escape = (bool) $flag;
        return $this;
    }

    /**
     * Set HTML tag with which to surround description
     *
     * @param  string $tag
     * @return \Zend_Form_Decorator_Description
     */
    public function setTag($tag)
    {
        $this->_tag = (string) $tag;
        return $this;
    }

}
