<?php
/**
 * Copyright (c) 2014, Erasmus MC
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
 *
 * @package    Gems
 * @subpackage Form
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Form_Decorator_CKEditor extends Zend_Form_Decorator_ViewHelper
{
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
		//MUtil_Echo::track('test');	
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
		$view = $element->getView();

		$baseUrl = GemsEscort::getInstance()->basepath->getBasePath() . '/';
		$view->headScript()->appendFile($baseUrl . $this->_basedir . '/' . 'ckeditor.js');
		

        //MUtil_Echo::track($baseUrl);
		//MUtil_Echo::track('test');
		$element = $this->getElement();
		$name = htmlentities($element->getFullyQualifiedName());
		$label = htmlentities($element->getLabel());
		$id = htmlentities($element->getId());
		$value = htmlentities($element->getValue());

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