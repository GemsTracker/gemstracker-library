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

/**
 * CKEditor view helper. Handles rendering the CKEditor element
 *
 * @package    Gems
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_View_Helper_FormCKEditor extends Zend_View_Helper_FormTextarea{
    public $config = array();
    public $basePath = '';

    public function formCKEditor($name = null, $value = null, $attribs = null, $options = null) {
        // Remove from attribs what we need to have in options
        foreach ($attribs as $key => $val) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($val);
                unset($attribs[$key]);
            }
        }

        include_once $this->basePath . '/ckeditor.php'; // Now make sure the CKEditor class can be loaded
        
        // Now set the location to the js/css
        $baseUrl = $this->view->serverUrl() . GemsEscort::getInstance()->basepath->getBasePath();
        $CKEditor = new CKEditor($baseUrl . '/gems/ckeditor/');
        $CKEditor->returnOutput = true; // We capture the output

        $output = $this->formTextarea($name, $value, $attribs); // Get regular textarea output
        $output = sprintf('<div class="ckeditor">%s</div>', $output); // Wrap in a div
        $output .= $CKEditor->replace($attribs['id'], $this->config); // And add init code for CKEditor

        return $output;
    }
    
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    public function setConfig($config) {
        $this->config = $config;
    }
}