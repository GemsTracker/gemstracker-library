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
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AutosearchFormSnippet.php 1280 2013-06-20 16:36:42Z matijsdejong $
 */

/**
 * Display survey answers with a toggle for full or compact view
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Snippets_Survey_Display_FullAnswerToggleSnippet extends MUtil_Snippets_SnippetAbstract {
    
    /**
     *
     * @var Zend_Controller_Request_Http
     */
    protected $request;
    
    public function getHtmlOutput(\Zend_View_Abstract $view) {
        $html = $this->getHtmlSequence();

        $request = $this->request;
        $html->hr(array('class'=>'noprint'));
        $params = $request->getParams();

        if (isset($params['fullanswers'])) {
            unset($params['fullanswers']);
        } else {
            $params['fullanswers'] = 1;
        }
        
        $url = array('controller' => $request->getControllerName(),
            'action' => $request->getActionName(),
            'routereset' => true) + $params;
        $html->a($url, $this->_('Toggle'), array('class' => 'actionlink'));
        $html->hr(array('class'=>'noprint'));

        return $html;
    }
    
    public function hasHtmlOutput() {
        // Only show toggle for individual answer display
        if ($this->request->getActionName() !== 'answer') {
            return false;
        }
        
        return parent::hasHtmlOutput();
    }
}