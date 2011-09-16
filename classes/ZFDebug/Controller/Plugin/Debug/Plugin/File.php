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
class ZFDebug_Controller_Plugin_Debug_Plugin_File implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'file';

    /**
     * Base path of this application
     * String is used to strip it from filenames
     *
     * @var string
     */
    protected $_basePath;

    /**
     * Stores included files
     *
     * @var array
     */
    protected $_includedFiles = null;

    /**
     * Stores name of own extension library
     *
     * @var string
     */
    protected $_library;

    /**
     * Setting Options
     *
     * basePath:
     * This will normally not your document root of your webserver, its your
     * application root directory with /application, /library and /public
     *
     * library:
     * Your own library extension(s)
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        isset($options['base_path']) || $options['base_path'] = $_SERVER['DOCUMENT_ROOT'];
        isset($options['library']) || $options['library'] = null;
        
        $this->_basePath = $options['base_path'];
        is_array($options['library']) || $options['library'] = array($options['library']);
        $this->_library = array_merge($options['library'], array('Zend', 'ZFDebug'));
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
        return count($this->_getIncludedFiles()) . ' Files';
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        $included = $this->_getIncludedFiles();
        $html = '<h4>File Information</h4>';
        $html .= count($included).' Files Included<br />';
        $size = 0;
        foreach ($included as $file) {
            $size += filesize($file);
        }
        $html .= 'Total Size: '. round($size/1024, 1).'K<br />';
        
        $html .= 'Basepath: ' . $this->_basePath . '<br />';

        $libraryFiles = array();
        foreach ($this->_library as $key => $value) {
            if ('' != $value) {
                $libraryFiles[$key] = '<h4>' . $value . ' Library Files</h4>';
            }
        }

        $html .= '<h4>Application Files</h4>';
        foreach ($included as $file) {
            $file = str_replace($this->_basePath, '', $file);
            $inUserLib = false;
        	foreach ($this->_library as $key => $library)
        	{
        		if('' != $library && false !== strstr($file, $library)) {
        			$libraryFiles[$key] .= $file . '<br />';
        			$inUserLib = TRUE;
        		}
        	}
        	if (!$inUserLib) {
    			$html .= $file . '<br />';
        	}
        }

    	$html .= implode('', $libraryFiles);

        return $html;
    }

    /**
     * Gets included files
     *
     * @return array
     */
    protected function _getIncludedFiles()
    {
        if (null !== $this->_includedFiles) {
            return $this->_includedFiles;
        }

        $this->_includedFiles = get_included_files();
        sort($this->_includedFiles);
        return $this->_includedFiles;
    }
}