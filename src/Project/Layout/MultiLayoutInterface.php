<?php

/**
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Project\Layout;

/**
 * Marker interface for Pulse Projects using only multi layout
 *
 * But \Gems_Project_Layout_MultiLayoutAbstract implements the functionality to use these functions.
 *
 * @see \Gems_Project_Layout_MultiLayoutAbstract
 *
 * @see \Gems\Project\Layout\SingleLayoutInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
interface MultiLayoutInterface
{
    /**
     * Returns an array with descriptions of the styles that can be used in this project.
     *
     * @return array styleKey => styleDescription
     */
    public function getStyles();

    /**
     * Performs the actual switch of the layout
     *
     * @param string $style Style, when null derived from request
     */
    public function layoutSwitch($style = null);
}