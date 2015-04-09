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
class MUtil_Loader_CachedLoader implements \Zend_Loader_Autoloader_Interface
{
    /**
     *
     * @var array
     */
    private $_cacheClassArray = array();

    /**
     *
     * @var array
     */
    // private $_cacheFileArray = array();

    /**
     *
     * @var boolean
     */
    private $_cacheChanged = false;

    /**
     * Unix timestamp cahce file load time
     *
     * @var int
     */
    private $_cacheLoadTime;

    /**
     *
     * @var string
     */
    private $_cacheDir  = null;

    /**
     *
     * @var string
     */
    private $_cacheFile = 'cached.loader.mutil.php';

    /**
     * An array containg include dir pathNames
     *
     * @var array
     */
    private $_includeDirs;

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
            throw new \Zend_Exception(sprintf('Cache directory %s does not exist.', $this->_cacheDir));
        }

        if (! is_writable($this->_cacheDir)) {
            throw new \Zend_Exception(sprintf('Cache directory %s is not writeable.', $this->_cacheFile));
        }

        if (! file_exists($this->_cacheFile)) {
            $this->_cacheChanged  = true;
            $this->_cacheLoadTime = 0;

            // $this->_saveCache();
        } else {
            if (! is_writable($this->_cacheFile)) {
                throw new \Zend_Exception(sprintf('Cache file %s not writeable.', $this->_cacheFile));
            }

            $this->_loadCache();
        }

        $this->_loadIncludePath();
        // \MUtil_Echo::track($this->_cacheFile, $this->_cacheDir, file_exists($this->_cacheDir));
    }

    /**
     * Make sure the changes ot the cache are saved.
     */
    public function __destruct()
    {
        $this->_saveCache();
    }

    /**
     * Check for file existence and append status to the cache
     *
     * @param mixed $file String path to file or false if does not exist
     * @return boolean True if the file exists
     */
    protected function _checkFile($file)
    {
        return file_exists($file);
        /*
        if (array_key_exists($file, $this->_cacheFileArray)) {
            return $this->_cacheFileArray[$file];
        }
        // \MUtil_Echo::track($file);

        $this->_cacheFileArray[$file] = file_exists($file);
        $this->_cacheChanged          = true;

        return $this->_cacheFileArray[$file];
        // */
    }

    /**
     * Loads the class from file with a check on changes to the include path
     */
    protected function _loadCache()
    {
    	$cacheArray = null;
    	$include    = null;
    	
        include $this->_cacheFile;

        // if (isset($cacheArray, $fileArray, $include) && (get_include_path() != $include)) {
        if (isset($cacheArray, $include) && (get_include_path() != $include)) {
            $this->_cacheClassArray = $cacheArray;
            // $this->_cacheFileArray  = $fileArray;
            $this->_cacheLoadTime = filemtime($this->_cacheFile);
        }

        $this->_cacheClassArray   = array();
        // $this->_cacheFileArray   = array();
        $this->_cacheChanged = true;
    }

    /**
     * Initialize the _includeDirs variable
     */
    protected function _loadIncludePath()
    {
        $dirs = \Zend_Loader::explodeIncludePath();

        foreach ($dirs as $dir) {
            if (('.' != $dir) && is_dir($dir)) {
                $this->_includeDirs[] = realpath($dir) . DIRECTORY_SEPARATOR;
;
            }
        }
    }

    /**
     * Save the cache to file (if changed)
     */
    protected function _saveCache()
    {
        // \MUtil_Echo::track(filemtime($this->_cacheFile), $this->_cacheLoadTime, $this->_cacheChanged);
        if (file_exists($this->_cacheFile)) {
            if ((! $this->_cacheChanged) && (filemtime($this->_cacheFile) >= $this->_cacheLoadTime)) {
                return;
            }
        }
        /*
        \MUtil_Echo::r('Saving load cache (from previous call)');

        include $this->_cacheFile;

        \MUtil_Echo::r(array_diff(array_keys($this->_cacheClassArray), array_keys($classArray)));
        // */

        $content = "<?php\n";
        $content .= "\$include = '" . get_include_path() . "';\n";

        ksort($this->_cacheClassArray);

        $content .= "\$classArray = array(\n";
        foreach ($this->_cacheClassArray as $class => $file) {
            $content .= "'$class' => '$file',\n";
        }
        $content .= ");\n";

        /*
        ksort($this->_cacheFileArray);

        $content .= "\$fileArray = array(\n";

        foreach ($this->_cacheFileArray as $file => $exists) {
            if (false === $exists) {
                $content .= "'$file' => false,\n";
            } else {
                $content .= "'$file' => true,\n";
            }
        }
        $content .= ");";
        // */

        file_put_contents($this->_cacheFile, $content, LOCK_EX);
    }

    /**
     * Autoload a class
     *
     * @abstract
     * @param   string $class
     * @return  mixed
     *          False [if unable to load $class]
     *          get_class($class) [if $class is successfully loaded]
     */
    public function autoload($class)
    {
        $className = ltrim($class, '\\');
        $file      = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $file      = strtr($namespace, '\\', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        $file .= strtr($className, '_', DIRECTORY_SEPARATOR) . '.php';

        if (isset($this->_cacheClassArray[$class])) {
            if ($this->_cacheClassArray[$class]) {
                return (boolean) $this->includeFile($this->_cacheClassArray[$class]);
            }
        } else {
            $dirs = $this->_includeDirs;

            foreach ($dirs as $dir) {
                // error_log($dir . $file);
                if ($this->includeFile($dir . $file)) {
                    $this->_cacheClassArray[$class] = $dir . $file;
                    $this->_cacheChanged            = true;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create a new instance of a class
     *
     * @param string $className The name of the class
     * @param array $arguments Class initialization arguments.
     * @return boolean True if the class exists.
     */
    public function createClass($className, array $arguments)
    {
        switch (count($arguments)) {
            case 0:
                return new $className();

            case 1:
                return new $className(
                    $arguments[0]
                    );

            case 2:
                return new $className(
                    $arguments[0], $arguments[1]
                    );

            case 3:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2]
                    );

            case 4:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3]
                    );

            case 5:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]
                    );

            case 6:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4],
                    $arguments[5]
                    );

            case 7:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4],
                    $arguments[5], $arguments[6]
                    );

            case 8:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4],
                    $arguments[5], $arguments[6], $arguments[7]
                    );

            case 9:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4],
                    $arguments[5], $arguments[6], $arguments[7], $arguments[8]
                    );

            case 10:
                return new $className(
                    $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4],
                    $arguments[5], $arguments[6], $arguments[7], $arguments[8], $arguments[9]
                    );

            default:
                throw new \Zend_Exception(
                        __CLASS__ . '->' . __FUNCTION__ . ' cannot create class with ' .
                        count($arguments) . ' parameters.'
                        );
        }
    }

    /**
     *
     * @static \MUtil_Loader_CachedLoader $instance
     * @param stirng $dir
     * @return \MUtil_Loader_CachedLoader
     */
    public static function getInstance($dir = null)
    {
        static $instance;

        if (! $instance) {
            $instance = new self($dir);

            if (is_subclass_of('Zend_Loader', 'MUtil_Loader_LoaderMarkerInterface')) {
                \Zend_Loader::setCachedLoader($instance);
            }
        }

        return $instance;;
    }

    /**
     * Include a file with cached existence check
     *
     * @param string $file The full path to the file
     * @return mixed The load return value if available, "1" if loaded without return, false otherwise
     */
    public function includeFile($file)
    {
        if (file_exists($file)) {
            $result = include $file;

            return $result ? $result : false;
        }

        return false;
    }

    /**
     * Load a class, but do not create it
     *
     * @param string $className The name of the class
     * @param string $file The full path to the file
     * @return boolean True if the class exists.
     */
    public function loadClass($className, $file)
    {
        if (isset($this->_cacheClassArray[$className])) {
            if ($this->_cacheClassArray[$className]) {
                $this->includeFile($this->_cacheClassArray[$className]);

                return true;
            } else {
                // return false;
            }
        }

        if (class_exists($className, false) || interface_exists($className, false)) {
            return true;
        }

        if ($this->includeFile($file)) {
            if (class_exists($className, false) || interface_exists($className, false)) {
                $this->_cacheClassArray[$className] = $file;
                $this->_cacheChanged                = true;
                return true;
            }

            throw new \Zend_Exception("The file '$file' does not contain the class '$className'.");
        }

        if (! isset($this->_cacheClassArray[$className])) {
            $this->_cacheClassArray[$className] = '';
            $this->_cacheChanged                = true;
        }

        return false;
    }

    /**
     * Loads a class from a PHP file.  The filename must be formatted
     * as "$class.php".
     *
     * If $dirs is a string or an array, it will search the directories
     * in the order supplied, and attempt to load the first matching file.
     *
     * If $dirs is null, it will split the class name at underscores to
     * generate a path hierarchy (e.g., "Zend_Example_Class" will map
     * to "Zend/Example/Class.php").
     *
     * If the file was not found in the $dirs, or if no $dirs were specified,
     * it will attempt to load it from PHP's include_path.
     *
     * @param string $class      - The full class name of a Zend component.
     * @param string|array $dirs - OPTIONAL Either a path or an array of paths
     *                             to search.
     * @return void
     * @throws \Zend_Exception
     */
    public function loadClassByPaths($class, $dirs = null)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }

        if ((null !== $dirs) && !is_string($dirs) && !is_array($dirs)) {
            require_once 'Zend/Exception.php';
            throw new \Zend_Exception('Directory argument must be a string or an array');
        }

        $className = ltrim($class, '\\');
        $file      = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $file      = strtr($namespace, '\\', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        $file .= strtr($className, '_', DIRECTORY_SEPARATOR) . '.php';

        if (isset($this->_cacheClassArray[$class])) {
            if ($this->_cacheClassArray[$class]) {
                $this->includeFile($this->_cacheClassArray[$class]);
            }
        } else {
            if (null === $dirs) {
                $dirs = $this->_includeDirs;
            } else {
                $dirs = array_merge($dirs, $this->_includeDirs);
            }

            foreach ($dirs as $dir) {
                // error_log($dir . $file);
                if ($this->includeFile($dir . $file)) {
                    $this->_cacheClassArray[$class] = $dir . $file;
                    $this->_cacheChanged            = true;
                    break;
                }
            }
        }

        if (!class_exists($class, false) && !interface_exists($class, false)) {
            require_once 'Zend/Exception.php';
            throw new \Zend_Exception("File \"$file\" does not exist or class \"$class\" was not found in the file");
        }
    }
}
