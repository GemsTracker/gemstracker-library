<?php

/**
 *
 * @package    Gems
 * @subpackage Registry
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends \MUtil_Registry_TargetAbstract with the ability to create PHP
 * callables by request an existing method using $this->methodName.
 *
 * @package    Gems
 * @subpackage Registry
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Registry_TargetAbstract extends \MUtil_Registry_TargetAbstract
{
    /**
     * Returns a callable if a method is called as a variable
     *
     * @param string $name
     * @return Callable
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return array($this, $name);
        }

        throw new \Gems_Exception_Coding("Unknown method '$name' requested as callable.");
    }
}
