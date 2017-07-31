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
}
