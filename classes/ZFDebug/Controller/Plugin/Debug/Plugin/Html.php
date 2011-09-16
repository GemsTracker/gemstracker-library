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
 */

/**
 * ZFDebug Zend Additions
 *
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 * @version    $Id$
 */

/**
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 */
class ZFDebug_Controller_Plugin_Debug_Plugin_Html implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'html';

    /**
     * Create ZFDebug_Controller_Plugin_Debug_Plugin_Html
     *
     * @param string $tab
     * @paran string $panel
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        return 'HTML';
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        $body = Zend_Controller_Front::getInstance()->getResponse()->getBody();
        $panel = '<h4>HTML Information</h4>';
        $panel .= '
        <script type="text/javascript" charset="utf-8">
            var ZFHtmlLoad = window.onload;
            window.onload = function(){
                if (ZFHtmlLoad) {
                    ZFHtmlLoad();
                }
                jQuery("#ZFDebug_Html_Tagcount").html(document.getElementsByTagName("*").length);
                jQuery("#ZFDebug_Html_Stylecount").html(jQuery("link[rel*=stylesheet]").length);
                jQuery("#ZFDebug_Html_Scriptcount").html(jQuery("script[src]").length);
                jQuery("#ZFDebug_Html_Imgcount").html(jQuery("img[src]").length);
            };
        </script>';
        $panel .= '<span id="ZFDebug_Html_Tagcount"></span> Tags<br />'
                . 'HTML Size: '.round(strlen($body)/1024, 2).'K<br />'
                . '<span id="ZFDebug_Html_Stylecount"></span> Stylesheet Files<br />'
                . '<span id="ZFDebug_Html_Scriptcount"></span> Javascript Files<br />'
                . '<span id="ZFDebug_Html_Imgcount"></span> Images<br />'
                . '<form method="POST" action="http://validator.w3.org/check" target="_blank"><input type="hidden" name="fragment" value="'.htmlentities($body).'"><input type="submit" value="Validate With W3"></form>';
        return $panel;
    }
}