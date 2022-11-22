<?php

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Loader;

use Gems\Exception\Coding;
use MUtil\EchoOut\EchoOut;
use MUtil\Registry\Source;
use MUtil\Registry\TargetInterface;
use Zalt\Late\StaticCall;
use Zalt\Loader\ProjectOverloader;

/**
 * LoaderAbstract is used for classes that chain from \Gems\Loader and that thus allow
 * projects to overrule the original implementation.
 *
 * I.e if you create a class <Project_name>_Model or <Project_name>_Util, that class is loaded
 * automatically instead of \Gems\Model or \Gems\Util. <Project_name>_Model should be a subclass
 * of \Gems\Model.
 *
 * You can set more than one overrule level. I.e. you can specify the class chain Demopulse,
 * Pulse, \Gems. The loader will then always look first in Demopulse, then in Pulse and
 * lastly in \Gems.
 *
 * The class inherits from \MUtil\Registry\Source as the chained classes may have values
 * that should be set automatically, e.g. from \Zend_Registry.
 *
 *
 * @package    Gems
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class LoaderAbstract extends Source
{
    /**
     * Allows sub classes of \Gems\Loader\LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected ?string $cascade = null;

    /**
     * @var array
     * @deprecated
     */
    protected array $_dirs = [];

    protected ProjectOverloader $_overLoader;

    /**
     *
     * @param mixed $container A container acting as source for \MUtil\Registry\Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct(ProjectOverloader $_overLoader)
    {
        parent::__construct($_overLoader);

        $this->_overLoader = $_overLoader;

        if ($this->cascade) {
            $this->_overLoader = $this->_overLoader->createSubFolderOverloader($this->cascade);
        }
        $this->_overLoader->setSource($this);

    }

    public function __get(string $name): callable
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return array($this, $name);
        }

        throw new Coding("Unknown property '$name' requested.");
    }

    protected function containerLoad(string $classname): ?object
    {
        if ($this->_overLoader instanceof ProjectOverloader) {
            $container = $this->_overLoader->getContainer();
            $resolvedClassName = $this->_overLoader->find($classname);
            if ($container->has($resolvedClassName)) {
                return $container->get($resolvedClassName);
            }
        }
        return null;
    }

    /**
     * Returns $this->$name, creating the item if it does not yet exist.
     *
     * @param string $name The $name of the variable to store this object in.
     * @param string $className Class name or null if the same as $name, prepending $this->_dirs.
     * @param array $arguments Class initialization arguments.
     * @return mixed Instance of $className
     */
    protected function _getClass(string $name, ?string $className = null, array $arguments = []): object
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
     * @see \MUtil\Lazy\StaticCall
     * @see \MUtil\Registry\TargetInterface
     *
     * @param string $name The class name, minus the part in $this->_dirs.
     * @param boolean $create Create the object, or only when an \MUtil\Registry\TargetInterface instance.
     * @param array $arguments Class initialization arguments.
     * @return mixed A class instance or a \MUtil\Lazy\StaticCall object
     */
    protected function _loadClass(string $name, bool $create = false, array $arguments = []): object
    {
        $className = $this->_overLoader->find($name);

        // \MUtil\EchoOut\EchoOut::track($className);

        if (is_subclass_of($className, __CLASS__)) {
            $create    = true;
            $arguments = array();

            if (isset($this->_containers[0])) {
                $arguments[] = $this->_containers[0];
            } else {
                $arguments[] = null;
            }

        } elseif (is_subclass_of($className, TargetInterface::class)) {
            $create = true;
        }

        if (! $create) {
            return new StaticCall($className);
        }

        $mergedArguments = array_values(array_merge(['className' => $className], $arguments));
        $obj = $this->_overLoader->create(...$mergedArguments);

        return $obj;
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param mixed $paths String or an array of strings
     * @param boolean $prepend Put path at the beginning of the stack (has no effect when prefix / dir already set)
     * @return \Gems\Loader\LoaderAbstract (continuation pattern)
     */
    public function addPrefixPath(string $prefix, string|array $path, bool $prepend = true): self
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

        $this->_overLoader->addOverloaders([$newPrefix]);

        if (Source::$verbose) {
            EchoOut::r($this->_dirs, '$this->_dirs in ' . get_class($this) . '->' . __FUNCTION__ . '():');
        }

        return $this;
    }
}
