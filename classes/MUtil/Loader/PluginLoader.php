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
 * @version    $Id: PluginLoader.php$
 */

/**
 * Extension of PluginLoader with class instantiation
 *
 * @package    MUtil
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Loader_PluginLoader extends Zend_Loader_PluginLoader
{
    /**
     * Add the default autoloader to this plugin loader.
     *
     * @return Zend_Loader_PluginLoader (continuation pattern)
     */
    public function addFallBackPath()
    {
        // Add each of the classpath directories to the prefixpaths
        // with an empty prefix.
        foreach (Zend_Loader::explodeIncludePath() as $include) {
            if ($real = realpath($include)) {
                parent::addPrefixPath('', $real);
            }
        }

        return $this;
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Loader_PluginLoader (continuation pattern)
     */
    public function addPrefixPath($prefix, $path)
    {
        if (!is_string($prefix) || !is_string($path)) {
            throw new Zend_Loader_PluginLoader_Exception('Zend_Loader_PluginLoader::addPrefixPath() method only takes strings for prefix and path.');
        }

        // MUtil_Echo::track(self::getAbsolutePaths($path));
        foreach (self::getAbsolutePaths($path) as $sub) {
            parent::addPrefixPath($prefix, $sub);
        }

        return $this;
    }

    /**
     * Instantiate a new class using the arguments array for initiation
     *
     * @param string $className
     * @param array $arguments Instanciation arguments
     * @return className
     */
    public function createClass($className, array $arguments = array())
    {
        if (!class_exists($className, false)) {
            $className = $this->load($className);
        }

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
                throw new Zend_Exception(
                        'MUtil Plugin Loader cannot create a class with ' .
                        count($arguments) . ' parameters.'
                        );
        }
    }

    /**
     * Add existing include path directories to subdirectory (if not absolute)
     *
     * @staticvar string $includePaths Array containing exploded and checked include path
     * @param string $path
     * @return array Can be empty if none of the options exist
     */
    public static function getAbsolutePaths($path)
    {
        static $includePaths;

        if ($path) {
            // Try to see if the path is an absolute path. Some exotic absolute paths can fail this test,
            // but it is more error prone to test for them here than to loop through them afterwards.
            if (self::isAbsolutePath($path)) {
                if ($real = realpath($path)) {
                    return array($real);
                } else {
                    return array();
                }
            }
        }

        if (! is_array($includePaths)) {
            // Make sure the include path are loaded
            foreach (Zend_Loader::explodeIncludePath() as $include) {
                // Current path will be checked, for each file
                // but check the other paths for exiistence
                if (('.' != $include) && ($real = realpath($include))) {
                    $includePaths[] = $real . DIRECTORY_SEPARATOR;
                }
            }
        }

        // Check path name
        $results = array();
        if ($real = realpath($path)) {
            $results[] = $real;
        }

        // Check simple concatenation
        foreach ($includePaths as $include) {
            if ($real = realpath($include . $path)) {
                $results[] = $real;;
            }
        }

        // Reverse the result as that is the order this loader handles the directories
        return array_reverse($results);
    }

    /**
     * Do a quick check for a path being absolute (may not work for some exotic absolute paths though)
     *
     * @param string $path
     * @return boolean
     */
    public static function isAbsolutePath($path)
    {
        if ($path) {
            // Try to see if the path is an absolute path. Some exotic absolute paths can fail this test,
            // but it is more error prone to test for them here than to loop through them afterwards.
            if (substr(PHP_OS, 0, 1) === 'WIN') {
                // Match for A:\ and \\ network paths
                if (preg_match('/([A-Za-z]:\\|\\\\)./', $path)) {
                    return true;
                }
            } else {
                if (strlen($path) && (DIRECTORY_SEPARATOR === $path[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Load a plugin via the name provided
     *
     * @param  string $name
     * @param  bool $throwExceptions Whether or not to throw exceptions if the
     * class is not resolved
     * @return string|false Class name of loaded class; false if $throwExceptions
     * if false and no class found
     * @throws Zend_Loader_Exception if class not found
     */
    public function load($name, $throwExceptions = true)
    {
        if ($this->isLoaded($name)) {
            return $this->getClassName($name);
        }
        $name = $this->_formatName($name);

        if ($this->_useStaticRegistry) {
            $registry = self::$_staticPrefixToPaths[$this->_useStaticRegistry];
        } else {
            $registry = $this->_prefixToPaths;
        }

        $registry  = array_reverse($registry, true);
        $found     = false;
        $classFile = str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
        $incFile   = self::getIncludeFileCache();
        foreach ($registry as $prefix => $paths) {
            $className = $prefix . $name;

            if (class_exists($className, false)) {
                $found = true;
                break;
            }

            $paths     = array_reverse($paths, true);

            foreach ($paths as $path) {
                $loadFile = $path . $classFile;
                // Can use file_exist now, as any paths in the class path that
                // could be use are already in use.
                // if (Zend_Loader::isReadable($loadFile)) {
                if (file_exists($loadFile)) {
                    // include_once $loadFile;
                    include $loadFile; // Is faster
                    if (class_exists($className, false)) {
                        if (null !== $incFile) {
                            self::_appendIncFile($loadFile);
                        }
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if (!$found) {
            if (!$throwExceptions) {
                return false;
            }

            $message = "Plugin by name '$name' was not found in the registry; used paths:";
            foreach ($registry as $prefix => $paths) {
                $message .= "\n$prefix: " . implode(PATH_SEPARATOR, $paths);
            }
            require_once 'Zend/Loader/PluginLoader/Exception.php';
            throw new Zend_Loader_PluginLoader_Exception($message);
       }

        if ($this->_useStaticRegistry) {
            self::$_staticLoadedPlugins[$this->_useStaticRegistry][$name]     = $className;
        } else {
            $this->_loadedPlugins[$name]     = $className;
        }
        return $className;
    }
}
