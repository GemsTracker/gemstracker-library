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
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * LoaderAbstract is used for classes that chain from Gems_Loader and that thus allow
 * projects to overrule the origingal implementation.
 *
 * I.e if you create a class <Project_name>_Model or <Project_name>_Util, that class is loaded
 * automatically instead of Gems_Model or Gems_Util. <Project_name>_Model should be a subclass
 * of Gems_Model.
 *
 * You can set more than one overrule level. I.e. you can specify the class chain Demopulse,
 * Pulse, Gems. The loader will then always look first in Demopulse, then in Pulse and
 * lastly in Gems.
 *
 * The class inherits from MUtil_Registry_Source as the chained classes may have values
 * that should be set automatically, e.g. from Zend_Registry.
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Loader_LoaderAbstract extends MUtil_Registry_Source
{
    /**
     * The prefix/path location to look for classes.
     *
     * The standard value is
     * - <Project_name> => application/classes
     * - Gems => library/Gems/classes
     *
     * But an alternative could be:
     * - Demopulse => application/classes
     * - Pulse => application/classes
     * - Gems => library/Gems/classes
     *
     * @var array Of prefix => path strings for class lookup
     */
    private $_dirs;

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = null;

    /**
     *
     * @param type $container A container acting as source fro MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container, array $dirs)
    {
        parent::__construct($container);

        $this->_dirs = $dirs;

        // Set the directories to the used cascade pattern
        if ($this->cascade) {
            foreach ($dirs as $prefix => $path) {
                $newdirs[$prefix . '_' . $this->cascade] = $path;
            }
            $this->_dirs = $newdirs;
        }
        if (MUtil_Registry_Source::$verbose) {
            MUtil_Echo::r($this->_dirs, '$this->_dirs in ' . get_class($this) . '->' . __FUNCTION__ . '():');
        }
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return array($this, $name);
        }

        throw new Gems_Exception_Coding("Unknown property '$name' requested.");
    }

    protected function _getClass($name, $className = null, array $arguments = array())
    {
        if (! isset($this->$name)) {
            if (null === $className) {
                $className = $name;
            }
            $this->$name = $this->_loadClass($className, true, $arguments);
        }

        return $this->$name;
    }

    protected function _loadClass($name, $create = false, array $arguments = array())
    {
        // echo $name . ($create ? ' create' : ' not created') . "<br/>\n";

        $cname = '_' . trim(str_replace('/', '_', ucfirst($name)), '_');
        $cfile = str_replace('_', '/', $cname) . '.php';

        foreach ($this->_dirs as $prefix => $path) {
            $fprefix = str_replace('_', '/', $prefix);
            if ($obj = $this->_loadClassPath($path . '/' . $fprefix . $cfile, $prefix . $cname, $create, $arguments)) {

                if ($obj instanceof MUtil_Registry_TargetInterface) {
                    if ((! $this->applySource($obj)) && parent::$verbose) {
                        MUtil_Echo::track("Source apply to object of type $name failed.");
                    }
                }

                return $obj;
            }
        }
        //print_r($this->_dirs);
    }

    private function _loadClassPath($filepath, $classname, $create, array $arguments)
    {
        // echo '_loadClassPath: ' . $this->cascade . '-' . $classname . '-' . ($create ? 1 : 0) . "<br/>\n";
        // debug_print_backtrace();
        // MUtil_Echo::track($filepath, $classname, $this->cascade);

        if (file_exists($filepath)) {
            if (! class_exists($classname)) {
                // echo $classname . ' :: ' . $filepath . "<br/>\n";
                include_once($filepath);
            }

            if (is_subclass_of($classname, __CLASS__)) {
                return new $classname($this->_containers[0], $this->_dirs);

            } elseif ($create || is_subclass_of($classname, 'MUtil_Registry_TargetInterface')) {
                switch (count($arguments)) {
                    case 0:
                        return new $classname();

                    case 1:
                        return new $classname($arguments[0]);

                    case 2:
                        return new $classname($arguments[0], $arguments[1]);

                    case 3:
                        return new $classname($arguments[0], $arguments[1], $arguments[2]);

                    default:
                        throw new Gems_Exception_Coding(__CLASS__ . '->' . __FUNCTION__ . ' cannot create class with ' . count($arguments) . ' parameters.');
                }

            } else {
                return new MUtil_Lazy_StaticCall($classname);
            }
        }
    }
}
