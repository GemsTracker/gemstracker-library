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
 * @version    $Id: Bootstrapper .php 1748 2014-02-19 18:09:41Z matijsdejong $
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
    protected $_version = MUtil_Bootstrap::DEFAULT_BOOTSTRAP_VERSION;

    /**
     * View Instance
     *
     * @var Zend_View_Interface
     */
    protected $_view = null;

    public function __toString()
    {

    }

    /**
     * Set Use SSL on CDN Flag
     *
     * @param bool $flag
     * @return MUtil_Bootstrap_View_Helper_Bootstrapper (continuation pattern)
     */
    public function setCdnSsl($flag)
    {
        $this->_loadSslCdnPath = (boolean) $flag;
        return $this;
    }

    /**
     * Set view object
     *
     * @param  Zend_View_Interface $view
     * @return MUtil_Bootstrap_View_Helper_Bootstrapper (continuation pattern)
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->_view = $view;
        $doctype = $this->_view->doctype();

        if ($doctype instanceof Zend_View_Helper_Doctype) {
            if (! $doctype->isHtml5()) {
                if ($doctype->isXhtml()) {
                    $doctype->setDoctype(Zend_View_Helper_Doctype::XHTML5);
                } else {
                    $doctype->setDoctype(Zend_View_Helper_Doctype::HTML5);
                }
            }
        }

        return $this;
    }
}
