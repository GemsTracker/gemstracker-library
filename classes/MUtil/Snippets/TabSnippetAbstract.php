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
 * @package    MUtil
 * @subpackage Snippets
 * @author     Menoo Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TabSnippetAbstract.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * Abstract class for quickly creating a tabbed bar, or rather a div that contains a number 
 * of links, adding specific classes for display.
 * 
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class MUtil_Snippets_TabSnippetAbstract extends MUtil_Snippets_SnippetAbstract
{
    /**
     * Optional standard url parts
     * 
     * @var array
     */
    protected $baseurl = array();
    
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'tabrow';

    /**
     *
     * @var string
     */
    protected $defaultTab;

    /**
     *
     * @var array
     */
    protected $href = array();

    /**
     * Optional: $request or $tokenData must be set
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    protected $tabActiveClass = 'active';
    protected $tabClass       = 'tab';

    abstract protected function getParameterKey();
    abstract protected function getTabs();

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        if (isset($parameters['baseurl']) && is_array($this->baseurl)) {
            $this->href = $this->href + $this->baseurl;
        }

        $param = $this->getParameterKey();
        $tabs  = $this->getTabs();

        // When empty, first is default
        if (null === $this->defaultTab) {
            reset($tabs);
            $this->defaultTab = key($tabs);
        }

        $argFilter = $this->request->getParam($param);
        $argFilter = $argFilter ? $argFilter : $this->defaultTab;

        $tabRow = MUtil_Html::create()->div();

        foreach ($tabs as $tabName => $content) {
            $this->href[$param] = ($tabName == $this->defaultTab) ? null : $tabName;

            $div = $tabRow->div(array('class' => $this->tabClass));
            $div->a($this->href, $content);

            if ($argFilter == $tabName) {
                $div->appendAttrib('class', $this->tabActiveClass);
            }
        }

        return $tabRow;
    }


}
