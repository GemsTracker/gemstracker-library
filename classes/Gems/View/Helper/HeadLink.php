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
 * Transparent LESS compiling for all headlink files with .less extension
 *
 * @package    Gems
 * @subpackage View\Helper
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

// We need to load the class first
include_once 'Lessphp/lessc.inc.php';

/**
 * Add transparent LESS compiling to the headlink
 *
 * Using http://leafo.net/lessphp/ this helper compiles http://lesscss.org/ to a css file.
 * Compiling takes some time, so it will only run when the input file is newer than
 * the output file or when the output file doesn't exist.
 * 
 * Append ?compilecss to the url to force it to create new css.
 *
 * @package    Gems
 * @subpackage View\Helper
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_View_Helper_HeadLink extends Zend_View_Helper_HeadLink
{
    public function itemToString(\stdClass $item)
    {
        $attributes = (array) $item;
        
        if (isset($attributes['type']) && $attributes['type'] == 'text/css') {
            // This is a stylesheet, consider extension and compile .less to .css
            if (substr($attributes['href'],-5) == '.less') {
                $href = $attributes['href'];
                if (substr($attributes['href'],0,4) === 'http') {
                    // This must be a local url, strip the serverUrl and basepath
                    $base = $this->view->serverUrl() . GemsEscort::getInstance()->basepath->getBasePath();
                    if ($base == substr($href, 0, strlen($base))) {
                        // Only strip when urls match
                        $href = substr($href, strlen($base));
                    }
                }

                // Add full path to the webdir
                $inFile = GEMS_WEB_DIR . $href;
                $outFile = substr($inFile, 0, -4) . 'css';

                // Try compiling
                try {
                    $params = Zend_Controller_Front::getInstance()->getRequest()->getParams();
                    if (array_key_exists('compilecss', $params)) {
                        $lessc = new lessc();
                        $result = $lessc->compileFile($inFile, $outFile);
                    } else {
                        $result = lessc::ccompile($inFile, $outFile);
                    }
                } catch (Exception $exc) {
                    // If we have an error, present it if not in production
                    if (APPLICATION_ENV !== 'production') {
                        MUtil_Echo::pre($exc->getMessage());
                    }
                }

                // Modify object, not the derived array
                $item->href = substr($attributes['href'], 0, -4) . 'css';
            }
        }
        
        return parent::itemToString($item);
    }
}