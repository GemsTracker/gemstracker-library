<?php

/**
 *
 * @package    Gems
 * @subpackage Form
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */
namespace Gems\Form\Decorator;

class CKEditor extends \Zend_Form_Decorator_ViewHelper {

    /**
     * Default basedir for CKEditor. Can be overwritten through the Decorator options.
     * @var string
     */
    private $_basedir = 'gems/ckeditor';

    /**
     * Element text
     * @var string
     */
    private $_format = '<textarea id="%s" name="%s">%s</textarea>';

    protected $_options = array();

	public function __construct($options = null)
	{
        // If basepath not set, try a default
        if ($options) {
            if (is_array($options)) {
                $this->_options = $options;
            } else {
                $this->_options[] = $options;
            }
            if (isset($this->_options['baseDir'])) {
                $this->_basedir = $this->_options['baseDir'];
            }
        }
    }

    public function render($content)
    {
        $element = $this->getElement();
        if (!$element instanceof \Zend_Form_Element) {
            throw new \RuntimeException('Element not found');
        }
        $view    = $element->getView();

        $baseUrl = '/';//\Gems\Escort::getInstance()->basepath->getBasePath() . '/';
        $view->headScript()->appendFile($baseUrl . $this->_basedir . '/' . 'ckeditor.js');


        //\MUtil\EchoOut\EchoOut::track($baseUrl);
        //\MUtil\EchoOut\EchoOut::track('test');
        $name    = htmlentities($element->getFullyQualifiedName());
        $label   = htmlentities($element->getLabel());
        $id      = htmlentities($element->getId());
        $value   = htmlentities($element->getValue());

        $markup = sprintf($this->_format, $id, $name, $value);

        $view = $element->getView();
        $view->inlineScript()->appendScript("
			if (typeof CKEditorConfig === 'undefined') {
				CKEditorConfig = {};
			}
			CKEDITOR.replace( '{$id}', CKEditorConfig );
		");

        return $markup;
    }
}
