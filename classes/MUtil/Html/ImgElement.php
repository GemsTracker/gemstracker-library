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
 * @author Matijs de Jong
 * @since 1.1
 * @version 1.4
 * @package MUtil
 * @subpackage Html
 */

/**
 * An image element with added functionality to automatically add with and height 
 * to the attributes.
 * 
 * When the 'src' attribute does not start with a '/' or with http or https a list 
 * of directories is searched.
 * 
 * The default list of directories is '/' and '/images/' but you can change the 
 * directories using addImageDir() or setImaageDir().
 * 
 * The class assumes the current working directory (getcwd()) is the web root
 * directory. When this is not the case use the setWebRoot() method.
 * 
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Html
 */
class MUtil_Html_ImgElement extends MUtil_Html_HtmlElement
{
    /**
     * @var array List of directory names where img looks for images.
     */
    private static $_imageDirs = array('/', '/images/');
    
    /**
     *
     * @var string The current web directory. Defaults to getcwd().
     */
    private static $_webRoot;

    /**
     * @var boolean|string When true, no content is used, when a string content is added to an attribute with that name.
     */
    protected $_contentToTag = 'alt';

    /**
     * Add a directory to the front of the list of search directories.
     * 
     * @param string $dir Directory name. Slashes added when needed.
     */
    public static function addImageDir($dir)
    {
        if (! $dir) {
            $dir = '/';
        } elseif ('/' != $dir[0]) {
            $dir = '/' . $dir;
        }
        if ('/' != $dir[strlen($dir) - 1]) {
            $dir .= '/';
        }

        if (! in_array($dir, self::$_imageDirs)) {
            array_unshift(self::$_imageDirs, $dir);
        }
    }
    
    /**
     * Searches for a matching image location and returns that location when found.
     * 
     * $filenames starting with a '/' or with http or https are skipped.
     * 
     * @param type $filename The src attribute as filename
     * @return string When a directory matches
     */
    public static function getImageDir($filename) 
    {
        if ($filename 
            && ('/' != $filename[0]) 
            && ('http://' != substr($filename, 0, 7)) 
            && ('https://' != substr($filename, 0, 8))) {

            $webRoot = self::getWebRoot();
            
            foreach (self::$_imageDirs as $dir) {
                if (file_exists($webRoot . $dir . $filename)) {
                    return $dir;
                }
            }
            if (MUtil_Html::$verbose) {
                MUtil_Echo::r("File not found: $filename. \n\nLooked in: \n" . implode(", \n", self::$_imageDirs));
            }
        }
    }

    /**
     * Returns the list of search directories. The first directory in the list is the first directory searched.
     * 
     * @return array Directory names with slashes added when needed.
     */
    public static function getImageDirs()
    {
        return self::$_imageDirs;
    }

    /**
     * Use this function to set the web root directory if your application uses chdir() anywhere.
     * 
     * @param string $webRoot The current webroot
     */
    public static function getWebRoot()
    {
        if (! self::$_webRoot) {
            self::$_webRoot = getcwd();
        }
        
        return self::$_webRoot;
    }

    /**
     * Static helper function for creation, used by @see MUtil_Html_Creator.
     * 
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_ImgElement
     */
    public static function img($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Function to allow overloading  of tag rendering only
     * 
     * Renders the element tag with it's content into a html string
     * 
     * The $view is used to correctly encode and escape the output
     *
     * @param Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    protected function renderElement(Zend_View_Abstract $view)
    {
        if (isset($this->_attribs['src'])) {
            $filename = $this->_attribs['src'];

            if ($dir = self::getImageDir($filename)) {
                if (! isset($this->_attribs['width'], $this->_attribs['height'])) {
                    try {
                        $info = getimagesize(self::getWebRoot() . $dir . $filename);

                        if (! isset($this->_attribs['width'])) {
                            $this->_attribs['width'] = $info[0];
                        }
                        if (! isset($this->_attribs['height'])) {
                            $this->_attribs['height'] = $info[1];
                        }
                    } catch (Exception $e) {
                        if (MUtil_Html::$verbose) {
                            MUtil_Echo::track($e);
                        }
                    }
                }

                $this->_attribs['src'] = $view->baseUrl() . $dir . $filename;
            }
            // MUtil_Echo::r($this->_attribs['src']);
        }

        return parent::renderElement($view);
    }

    /**
     * Sets the list of search directories. The last directory in the list is the first directory searched for the file.
     * 
     * @param array $dirs Directory names. Slashes added when needed.
     */
    public static function setImageDirs(array $dirs)
    {
        self::$_imageDirs = array();

        foreach ($dirs as $dir) {
            self::addImageDir($dir);
        }
    }

    /**
     * Use this function to set the web root directory if your application uses chdir() anywhere.
     * 
     * @param string $webRoot The current webroot
     */
    public static function setWebRoot($webRoot)
    {
        self::$_webRoot = $webRoot;
    }
}
