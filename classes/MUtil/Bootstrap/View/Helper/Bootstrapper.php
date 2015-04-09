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
 * @package    MUtil
 * @subpackage Bootstrap
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Bootstrapper.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    MUtil
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Bootstrap_View_Helper_Bootstrapper
{
    protected $_bootstrapScriptPath;

    protected $_bootstrapStylePath;

    protected $_fontawesomeStylePath;

    /**
     * Load CDN Path from SSL or Non-SSL?
     *
     * @var boolean
     */
    protected $_loadSslCdnPath = false;

    /**
     * Default CDN jQuery Library version
     *
     * @var String
     */
    protected $_version = \MUtil_Bootstrap::DEFAULT_BOOTSTRAP_VERSION;
    protected $_fontawesomeVersion = \MUtil_Bootstrap::DEFAULT_FONTAWESOME_VERSION;

    /**
     * View Instance
     *
     * @var \Zend_View_Interface
     */
    public $view = null;

    protected function _getBootstrapCdnPath()
    {
        return \MUtil_Bootstrap::CDN_BASE;
    }

    /**
     * Internal function that constructs the include path of the jQuery library.
     *
     * @return string
     */
    protected function _getBootstrapScriptPath()
    {
        if($this->_bootstrapScriptPath != null) {
            $source = $this->_bootstrapScriptPath;
        } else {
            $baseUri = $this->_getBootstrapCdnPath();
            $source  = $baseUri
                     . $this->getVersion()
                     . \MUtil_Bootstrap::CDN_JS;
        }

        return $source;
    }

    protected function _getFontAwesomeCdnPath()
    {
        return \MUtil_Bootstrap::CDN_FONTAWESOME_BASE;
    }

    /**
     * Internal function that constructs the include path of the jQuery library.
     *
     * @return string
     */
    protected function _getStylesheet()
    {
        if($this->_bootstrapStylePath != null) {
            $source = $this->_bootstrapStylePath;
        } else {
            $baseUri = $this->_getBootstrapCdnPath();
            $source  = $baseUri
                     . $this->getVersion()
                     . \MUtil_Bootstrap::CDN_CSS;
        }

        return $source;
    }

    protected function _getFontAwesomeStylesheet()
    {
        if($this->_fontawesomeStylePath != null) {
            $source = $this->_fontawesomeStylePath;
        } else {
            $baseUri = $this->_getFontAwesomeCdnPath();
            $source  = $baseUri
                     . $this->getFontAwesomeVersion()
                     . \MUtil_Bootstrap::CDN_FONTAWESOME_CSS;
        }

        return $source;
    }

    /**
     * Sets the (local) Script path to overwrite CDN loading
     * @param string path
     */
    public function setBootstrapScriptPath($path)
    {
        $this->_bootstrapScriptPath = $path;
    }

    /**
     * Sets the (local) Stylesheet path to overwrite CDN loading
     * @param string path
     */
    public function setBootstrapStylePath($path)
    {
        $this->_bootstrapStylePath = $path;
    }

    /**
     * Sets the (local) Font Awesome Stylesheet path to overwrite CDN loading
     * @param string path
     */
    public function setFontAwesomeStylePath($path)
    {
        $this->_fontawesomeStylePath = $path;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getFontAwesomeVersion()
    {
        return $this->_fontawesomeVersion;
    }

    /**
     * Renders all javascript file related stuff of the jQuery enviroment.
     *
     * @return string
     */
    public function renderJavascript()
    {

        $source = $this->_getBootstrapScriptPath();
        $scriptTags = '<script type="text/javascript" src="' . $source . '"></script>' . PHP_EOL;

        return $scriptTags;
    }

    /**
     * Render Bootstrap stylesheet(s)
     *
     * @return string
     */
    public function renderStylesheets()
    {
        $stylesheet = $this->_getStylesheet();

        if ($this->view instanceof \Zend_View_Abstract) {
            $closingBracket = ($this->view->doctype()->isXhtml()) ? ' />' : '>';
        } else {
            $closingBracket = ' />';
        }
        // disable the stylesheat loader for bootstrap now that it gets compiled with the style
        $style = ''; //'<link rel="stylesheet" href="'.$stylesheet.'" type="text/css" media="screen"' . $closingBracket . PHP_EOL;

        if (\MUtil_Bootstrap::$fontawesome === true) {
            $fontawesomeStylesheet = $this->_getFontAwesomeStylesheet();

            $style .= '<link rel="stylesheet" href="'.$fontawesomeStylesheet.'" type="text/css" media="screen"' . $closingBracket . PHP_EOL;
        }

        return $style;
    }



    /**
     * Set Use SSL on CDN Flag
     *
     * @param bool $flag
     * @return \MUtil_Bootstrap_View_Helper_Bootstrapper (continuation pattern)
     */
    public function setCdnSsl($flag)
    {
        $this->_loadSslCdnPath = (boolean) $flag;
        return $this;
    }

    /**
     * Set view object
     *
     * @param  \Zend_View_Interface $view
     * @return \MUtil_Bootstrap_View_Helper_Bootstrapper (continuation pattern)
     */
    public function setView(\Zend_View_Interface $view)
    {
        $this->view = $view;
        /*$doctype = $this->_view->doctype();

        if ($doctype instanceof \Zend_View_Helper_Doctype) {
            if (! $doctype->isHtml5()) {
                if ($doctype->isXhtml()) {
                    $doctype->setDoctype(\Zend_View_Helper_Doctype::XHTML5);
                } else {
                    $doctype->setDoctype(\Zend_View_Helper_Doctype::HTML5);
                }
            }
        }*/

        return $this;
    }
}