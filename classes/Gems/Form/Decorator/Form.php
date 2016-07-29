<?php
/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Takes care of adding css or javascript when the form is rendered
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Form_Decorator_Form extends \Zend_Form_Decorator_Form
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

        /*
         * Check if this is a form that should autosubmit. If so, add script to head and onload
         */
        if ($form->isAutoSubmit()) {
            $form->addScript($this->localScriptFiles);
            //\ZendX_JQuery::enableForm($form);
            $jquery = $view->jQuery();
            $jquery->enable();  //Just to make sure

            $params = $form->getAutoSubmit();
            if (($view instanceof \Zend_View_Abstract) && ($params['submitUrl'] instanceof \MUtil_Html_HtmlInterface)) {
                $params['submitUrl'] = $params['submitUrl']->render($view);
            }

            $js = sprintf(
                    '%s("#%s").%s(%s);',
                    \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                    $form->getId(),
                    $this->localScriptName,
                    \ZendX_JQuery::encodeJson($params)
                    );
            $jquery->addOnLoad($js);
        }

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
