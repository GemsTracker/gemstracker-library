<?php

/**
 * @package    Gems
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * TargetLoaderAbstract is used for classes that chain from \Gems_Loader but are
 * also a target themselves.
 *
 * As these classes may need setting of values this subclass implements the
 * checkRegistryRequestsAnswers() easy access to resources.
 *
 * @package    Gems
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Loader_TargetLoaderAbstract extends \Gems_Loader_LoaderAbstract implements \MUtil_Registry_TargetInterface
{
    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    { }

    /**
     * Allows the loader to set resources.
     *
     * @param string $name Name of resource to set
     * @param mixed $resource The resource.
     * @return boolean True if $resource was OK
     */
    public function answerRegistryRequest($name, $resource)
    {
        $this->$name = $resource;

        return true;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return true;
    }

    /**
     * Filters the names that should not be requested.
     *
     * Can be overriden.
     *
     * @param string $name
     * @return boolean
     */
    protected function filterRequestNames($name)
    {
        return ('_' !== $name[0]) && ('cascade' !== $name);
    }

    /**
     * Allows the loader to know the resources to set.
     *
     * Returns those object variables defined by the subclass but not at the level of this definition.
     *
     * Can be overruled.
     *
     * @return array of string names
     */
    public function getRegistryRequests()
    {
        // Filter using the $this->filterRequestNames() callback
        return array_filter(array_keys(get_object_vars($this)), array($this, 'filterRequestNames'));
    }
    
    /**
     * Returns a list of selectable classes with an empty element as the first option.
     *
     * @param string $classType The class or interface that must me implemented
     * @param array  $paths Array of prefix => path to search
     * @param string $nameMEthod The method to call to get the name of the class
     * @return [] array of classname => name
     */
    public function listClasses($classType, $paths, $nameMethod = 'getName')
    {
        $results   = array();
        
        foreach ($paths as $prefix => $path) {
            $parts = explode('_', $prefix, 2);
            if ($name = reset($parts)) {
                $name = ' (' . $name . ')';
            }
            
            try {
                $globIter = new \GlobIterator($path . DIRECTORY_SEPARATOR . '*.php');
            } catch (\RuntimeException $e) {
                // We skip invalid dirs
                continue;
            }
            
            foreach($globIter as $fileinfo) {
                $filename    = $fileinfo->getFilename();
                $className   = $prefix . substr($filename, 0, -4);
                $classNsName = '\\' . strtr($className, '_', '\\');

                // Take care of double definitions
                if (isset($results[$className])) {
                    continue;
                }
                
                if (! (class_exists($className, false) || class_exists($classNsName, false))) {
                    include($path . DIRECTORY_SEPARATOR . $filename);
                }

                if ((! class_exists($className, false)) && class_exists($classNsName, false)) {
                    $className = $classNsName;
                }
                $class = new $className();

                if ($class instanceof $classType) {
                    if ($class instanceof \MUtil_Registry_TargetInterface) {
                        $this->applySource($class);
                    }

                    $results[$className] = trim($class->$nameMethod()) . $name;
                }
                // \MUtil_Echo::track($eventName);
            }
            
        }
        natcasesort($results);
        return $results;
    }
}
