<?php
/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Takes care of adding css or javascript when the form is rendered
 *
 * $Id$
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class Gems_Form_Decorator_Form extends Zend_Form_Decorator_Form
{
    protected $localScriptFiles = '/gems/js/autoSubmitForm.js';
    protected $localScriptName = 'autoSubmitForm';

    public function render($content) {
    	$form	 	= $this->getElement();
    	$view 		= $form->getView();

        /*
         * Check if this is a form that should autosubmit. If so, add script to head and onload
         */
        if ($form->isAutoSubmit()) {
            $form->addScript($this->localScriptFiles);
            //ZendX_JQuery::enableForm($form);
            $jquery = $view->jQuery();
            $jquery->enable();  //Just to make sure

            $params = $form->getAutoSubmit();
            if (($view instanceof Zend_View_Abstract) && ($params['submitUrl'] instanceof MUtil_Html_HtmlInterface)) {
                $params['submitUrl'] = $params['submitUrl']->render($view);
            }

            $js = sprintf('%s("#%s").%s(%s);',
            ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
            $form->getId(),
            $this->localScriptName,
            ZendX_JQuery::encodeJson($params)
            );
            $jquery->addOnLoad($js);
        }

        $scripts 	= $form->getScripts();
        $css        = $form->getCss();

        if (!is_null($scripts) && is_array($scripts)) {
    		$baseUrl = $view->serverUrl() . GemsEscort::getInstance()->basepath->getBasePath();
            $headscript = $view->headScript();
	        foreach ($scripts as $script) {
	        	$headscript->appendFile($baseUrl . $script);
	        }
    	}
        if (!is_null($css) && is_array($css)) {
            $baseUrl = $view->serverUrl() . GemsEscort::getInstance()->basepath->getBasePath();
            $headLink = $view->headLink();
            foreach($css as $cssFile => $media) {
                $headLink->appendStylesheet($baseUrl . $cssFile, $media);
            }
        }
    	$content = parent::render($content);
    	return $content;
    }
}
