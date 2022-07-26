<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Application
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

/**
 *
 * @package    Gems
 * @subpackage Event\Application
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class LoaderInitEvent extends Event
{
    const NAME = 'gems.loader.init';

    /**
     * @var \Zend_Registry|\Zalt\Loader\ProjectOverloader
     */
    protected $container;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * LoaderInitEvent constructor.
     *
     * @param \Gems\Loader   $loader
     * @param mixed $container
     */
    public function __construct(\Gems\Loader $loader, $container)
    {
        $this->loader    = $loader;
        $this->container = $container;

        if (! isset($this->container->source)) {
            $this->container->source = $loader;
        }
    }

    /**
     * Add object to the base container
     *
     * @param mixed $object
     * @param string $name
     */
    public function addByName($object, $name)
    {
        if ($object instanceof \MUtil\Registry\TargetInterface) {
            $this->applySource($object);
        }
        $this->container->getServiceManager()->setService($name, $object);
    }

    /**
     * Adds an extra source container to this object.
     *
     * @param mixed $container \Zend_Config, array or \ArrayObject
     * @param string $name An optional name to identify the container
     */
    public function addRegistryContainer($container, $name = null)
    {
        $this->loader->addRegistryContainer($container, $name);
    }

    /**
     * Apply this source to the target.
     *
     * @param \MUtil\Registry\TargetInterface $target
     * @return boolean True if $target is OK with loaded requests
     */
    public function applySource(\MUtil\Registry\TargetInterface $target)
    {
        return $this->loader->applySource($target);
    }
}
