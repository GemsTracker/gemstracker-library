<?php

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Menu;

/**
 * The ParameterSourceInterface marks a class as being
 * able to return data to set a named parameter for a
 * SubMenuItem.
 *
 * @see \Gems\Menu\SubMenuItem
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
interface ParameterSourceInterface
{
    /**
     * Returns a value to use as parameter for $name or
     * $default if this object does not contain the value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
   public function getMenuParameter($name, $default);
}
