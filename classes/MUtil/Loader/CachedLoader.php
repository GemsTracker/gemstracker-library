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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CachedLoader.php$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Loader_CachedLoader
{
    /**
     *
     * @var array
     */
    protected $_cacheArray = array();

    /**
     *
     * @var string
     */
    protected $_cacheDir  = null;

    /**
     *
     * @var string
     */
    protected $_cacheFile = 'cached.loader.mutil.php';

    /**
     *
     * @param string $dir
     */
    protected function __construct($dir = null)
    {
        if (null === $dir) {
            $dir = getenv('TMP');
        }

        $this->_cacheDir  = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR;
        $this->_cacheFile = $this->_cacheDir . $this->_cacheFile;

        if (! file_exists($this->_cacheDir)) {
            throw new Zend_Exception(sprintf('Cache directory %s does not exist.', $this->_cacheDir));
        }

        if (! is_writable($this->_cacheDir)) {
            throw new Zend_Exception(sprintf('Cache directory %s is not writeable.', $this->_cacheFile));
        }

        if (! file_exists($this->_cacheFile)) {
            $this->_saveCache();
        } else {
            if (! is_writable($this->_cacheFile)) {
                throw new Zend_Exception(sprintf('Cache file %s not writeable.', $this->_cacheFile));
            }

            $this->_loadCache();
        }
        // MUtil_Echo::track($this->_cacheFile, $this->_cacheDir, file_exists($this->_cacheDir));
    }

    /**
     * Append a new found file to the cache
     *
     * @param string $class The name of the class to load
     * @param mixed $file String path to file or false if does not exist
     */
    protected function _appendToCache($class, $file)
    {
        $this->_cacheArray[$class] = $file;

        if (! file_exists($this->_cacheFile)) {
            $this->_saveCache();
        } else {
            if (false === $file) {
                $content = "\$this->_cacheArray['$class'] = false;\n";
            } else {
                $content = "\$this->_cacheArray['$class'] = '$file';\n";
            }
            file_put_contents($this->_cacheFile, $content, FILE_APPEND | LOCK_EX );
        }
    }

    protected function _loadCache()
    {
        include $this->_cacheFile;
    }

    protected function _saveCache()
    {
        $content = "<?php\n";

        foreach ($this->_cacheArray as $class => $file) {
            if (false === $file) {
                $content .= "\$this->_cacheArray['$class'] = false;\n";
            } else {
                $content .= "\$this->_cacheArray['$class'] = '$file';\n";
            }
        }
        file_put_contents($this->_cacheFile, $content, LOCK_EX);
    }

    /**
     *
     * @static MUtil_Loader_CachedLoader $instance
     * @param stirng $dir
     * @return MUtil_Loader_CachedLoader
     */
    public static function getInstance($dir = null)
    {
        static $instance;

        if (! $instance) {
            $instance = new self($dir);
        }

        return $instance;;
    }

    protected function isClass($className, $paths)
    {
        // Zend_Loader::standardiseFile($file)
        //if (file_exists($path . $className)) {

//        /}
    }
}
