<?php
/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Form\Decorator;

/**
 * Takes care of adding css or javascript when the form is rendered
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Form extends \Zend_Form_Decorator_Form
{
    protected $localScriptFiles = '/gems/js/autoSubmitForm.js';
    protected $localScriptName = 'autoSubmitForm';

    /**
     * Render a form
     *
     * Replaces $content entirely from currently set element.
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
    	$form = $this->getElement();
    	$view = $form->getView();

        $scripts 	= $form->getScripts();
        $css        = $form->getCss();

        if (!is_null($scripts) && is_array($scripts)) {
    		$baseUrl = $view->serverUrl() . $view->baseUrl();
            $headscript = $view->headScript();
	        foreach ($scripts as $script) {
	        	$headscript->appendFile($baseUrl . $script);
	        }
    	}
        if (!is_null($css) && is_array($css)) {
            $baseUrl = $view->serverUrl() . $view->baseUrl();
            $headLink = $view->headLink();
            foreach($css as $cssFile => $media) {
                $headLink->appendStylesheet($baseUrl . $cssFile, $media);
            }
        }
    	$content = parent::render($content);
    	return $content;
    }
}
