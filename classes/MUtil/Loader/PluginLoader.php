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
        parent::addPrefixPath('', '');

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

        if ($path) {
            if (('/' == $path[0]) || ('.' == $path[0]) || ((strlen($path) > 1) && (':' == $path[1]))) {
                // Only add existing directories
                if (! file_exists(rtrim($path, '/\\'))) {
                    return $this;
                }
            }
        }
        parent::addPrefixPath($prefix, $path);

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
}
