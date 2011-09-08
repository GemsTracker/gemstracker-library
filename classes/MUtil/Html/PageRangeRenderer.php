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
 * @version    $Id: PageRangeRenderer.php 345 2011-07-28 08:39:24Z 175780 $
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
 
/**
 * @version    $Id: PageRangeRenderer.php 345 2011-07-28 08:39:24Z 175780 $
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class MUtil_Html_PageRangeRenderer implements MUtil_Html_HtmlInterface, MUtil_Lazy_Procrastinator
{
    protected $_current;
    protected $_element;
    protected $_glue;
    protected $_lazy;
    protected $_panel;

    public $page;

    public function __construct(MUtil_Html_PagePanel $panel, $glue = ' ', $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), array('panel' => 'MUtil_Html_PagePanel', 'glue'), array('glue' => ' '));

        if (isset($args['panel'])) {
            $this->_panel = $args['panel'];
            unset($args['panel']);
        } else {
            throw new MUtil_Html_HtmlException('Illegal argument: no panel passed to ' . __CLASS__ . ' constructor.');
        }

        if (isset($args['glue'])) {
            $this->setGlue($args['glue']);
            unset($args['glue']);
        } else {
            $this->setGlue($glue);
        }

        $page = $this->toLazy()->page;
        $args = array($page) + $args;

        // We create the element here as this creates as an element using the specifications at this moment.
        // If created at render time the settings might have changed, introducing hard to trace bugs.
        $this->_element = $panel->createPageLink($this->toLazy()->notCurrent(), $page, $args);
    }

    public function getGlue()
    {
        return $this->_glue;
    }

    public function notCurrent()
    {
        // MUtil_Echo::r($this->page, $this->_current);
        return $this->page != $this->_current;
    }

    public function render(Zend_View_Abstract $view)
    {
        $html  = '';
        $glue  = $this->getGlue();
        $pages = $this->_panel->getPages();

        $this->_current = $pages->current;

        foreach ($pages->pagesInRange as $page) {
            $this->page = $page;

            $html .= $glue;
            $html .= $this->_element->render($view);
        }

        return substr($html, strlen($glue));

    }

    public function setGlue($glue)
    {
        $this->_glue = $glue;

        return $this;
    }

    public function toLazy()
    {
        if (! $this->_lazy) {
            $this->_lazy = new MUtil_Lazy_ObjectWrap($this);
        }

        return $this->_lazy;
    }
}

