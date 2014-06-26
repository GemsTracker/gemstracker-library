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
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_JQuery_View_Helper_ColorPicker extends ZendX_JQuery_View_Helper_ColorPicker {
    public function colorPicker($id, $value='', array $params=array(), array $attribs=array())
    {
	    $attribs = $this->_prepareAttributes($id, $value, $attribs);

	    if(strlen($value) >= 6) {
	        $params['color'] = $value;
	    }
            
            $params['showInput'] = true;
            $params['preferredFormat'] = "hex";

	    if(count($params) > 0) {
            $params = ZendX_JQuery::encodeJson($params);
	    } else {
	        $params = "{}";
	    }

        $js = sprintf('%s("#%s").spectrum(%s);',
            ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
            $attribs['id'],
            $params
        );

        $this->jquery->addOnLoad($js);
        
        $baseUrl = GemsEscort::getInstance()->basepath->getBasePath();
        $this->view->headScript()->appendFile($baseUrl . '/gems/spectrum/spectrum.js');
        $this->view->headLink()->appendStylesheet($baseUrl . '/gems/spectrum/spectrum.css');
        //$z = new Zend_View_Helper_HeadStyle()->append($baseUrl)
        
	    return $this->view->formText($id, $value, $attribs);
    }
}
