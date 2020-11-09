<?php

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * LoaderAbstract is used for classes that chain from \Gems_Loader and that thus allow
 * projects to overrule the original implementation.
 *
 * I.e if you create a class <Project_name>_Model or <Project_name>_Util, that class is loaded
 * automatically instead of \Gems_Model or \Gems_Util. <Project_name>_Model should be a subclass
 * of \Gems_Model.
 *
 * You can set more than one overrule level. I.e. you can specify the class chain Demopulse,
 * Pulse, Gems. The loader will then always look first in Demopulse, then in Pulse and
 * lastly in Gems.
 *
 * The class inherits from \MUtil_Registry_Source as the chained classes may have values
 * that should be set automatically, e.g. from \Zend_Registry.
 *
 *
 * @package    Gems
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Loader_LoaderAbstract extends \MUtil_Registry_Source
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
    protected $_dirs = array();

    /**
     *
     * @var \MUtil_Loader_PluginLoader
     */
    protected $_loader;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = null;

    /**
     *
     * @param mixed $container A container acting as source for \MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container, array $dirs)
    {
        parent::__construct($container);

        if ($this->cascade) {
            $this->_dirs = $this->_cascadedDirs($dirs, $this->cascade, true);
        } else {
            $this->_dirs = $dirs;
        }

        if ($container instanceof \Zalt\Loader\ProjectOverloader) {
            $this->_loader = $container;
            if ($this->cascade) {
                $this->_loader = $this->_loader->createSubFolderOverloader($this->cascade);
            }
            $this->_loader->setSource($this);
        } else {
            $this->_loader = new \MUtil_Loader_PluginLoader($this->_dirs);
        }

        if (\MUtil_Registry_Source::$verbose) {
            \MUtil_Echo::r($this->_dirs, '$this->_dirs in ' . get_class($this) . '->' . __FUNCTION__ . '():');
        }
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return array($this, $name);
        }

        throw new \Gems_Exception_Coding("Unknown property '$name' requested.");
    }

    /**
     * Add a subdirectory / sub name to a list of class load paths
     *
     * @param array $dirs prefix => path
     * @param string $cascade The sub directories to cascade to
     * @param boolean $fullClassnameFallback Allows full class name specification instead of just plugin name part
     * @return array prefix => path
     */
    protected function _cascadedDirs(array $dirs, $cascade, $fullClassnameFallback = true)
    {
        // Allow the use of the full class name instead of just the plugin part of the
        // name during load.
        if ($fullClassnameFallback) {
            $newdirs = array('' =>'');
        } else {
            $newdirs = array();
        }

        $cascadePath = '/' . strtr($cascade, '_\\', '//');
        $cascadeCls  = '_' . strtr($cascade, '/\\', '__');
        foreach ($dirs as $prefix => $path) {
            // Do not cascade a full classname fallback
            if ($prefix) {
                $newdirs[$prefix . $cascadeCls] = $path . $cascadePath;
            }
        }
        return $newdirs;
    }

    /**
     * Returns $this->$name, creating the item if it does not yet exist.
     *
     * @param string $name The $name of the variable to store this object in.
     * @param string $className Class name or null if the same as $name, prepending $this->_dirs.
     * @param array $arguments Class initialization arguments.
     * @return mixed Instance of $className
     */
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

    /**
     * Create or loads the class. When only loading, this function returns a StaticCall object that
     * can be invoked lazely.
     *
     * @see \MUtil_Lazy_StaticCall
     * @see \MUtil_Registry_TargetInterface
     *
     * @param string $name The class name, minus the part in $this->_dirs.
     * @param boolean $create Create the object, or only when an \MUtil_Registry_TargetInterface instance.
     * @param array $arguments Class initialization arguments.
     * @return mixed A class instance or a \MUtil_Lazy_StaticCall object
     */
    protected function _loadClass($name, $create = false, array $arguments = array())
    {
        if ($this->_loader instanceof Zalt\Loader\ProjectOverloader) {
            $className = $this->_loader->find($name);
         } else {
            $className = $this->_loader->load($name);
        }

        // \MUtil_Echo::track($className);

        if (is_subclass_of($className, __CLASS__)) {
            $create    = true;
            $arguments = array();

            if (isset($this->_containers[0])) {
                $arguments[] = $this->_containers[0];
            } else {
                $arguments[] = null;
            }

            $arguments[] = $this->_dirs;

            if ($this->_loader instanceof Zalt\Loader\ProjectOverloader) {
                $arguments[] = $this->_loader;
            }

        } elseif (is_subclass_of($className, 'MUtil_Registry_TargetInterface')) {
            $create = true;
        }

        if (! $create) {
            return new \MUtil_Lazy_StaticCall($className);
        }

        if ($this->_loader instanceof Zalt\Loader\ProjectOverloader) {
            $mergedArguments = array_merge(['className' => $className], $arguments);
            $obj = call_user_func_array([$this->_loader, 'create'], $mergedArguments);
        } else {
            $obj = $this->_loader->createClass($className, $arguments);

            if ($obj instanceof \MUtil_Registry_TargetInterface) {
                if ((! $this->applySource($obj)) && parent::$verbose) {
                    \MUtil_Echo::r("Source apply to object of type $name failed.", __CLASS__ . '->' .  __FUNCTION__);
                }
            }
        }

        return $obj;
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param mixed $paths String or an array of strings
     * @param boolean $prepend Put path at the beginning of the stack (has no effect when prefix / dir already set)
     * @return \Gems_Loader_LoaderAbstract (continuation pattern)
     */
    public function addPrefixPath($prefix, $path, $prepend = true)
    {
        if ($this->cascade) {
            $newPrefix = $prefix . '_' . $this->cascade;
            $newPath = $path . '/' . strtr($this->cascade, '_', '/');
        } else {
            $newPrefix = $prefix;
            $newPath = $path;
        }

        if ($prepend) {
            $this->_dirs = array($newPrefix => $newPath) + $this->_dirs;
        } else {
            $this->_dirs[$newPrefix] = $newPath;
        }
        
        if ($this->_loader instanceof Zalt\Loader\ProjectOverloader) {
            $this->_loader->addOverloaders([$newPrefix]);
        } else {
            $this->_loader->addPrefixPath($newPrefix, $newPath, $prepend);
        }

        if (\MUtil_Registry_Source::$verbose) {
            \MUtil_Echo::r($this->_dirs, '$this->_dirs in ' . get_class($this) . '->' . __FUNCTION__ . '():');
        }

        return $this;
    }
}