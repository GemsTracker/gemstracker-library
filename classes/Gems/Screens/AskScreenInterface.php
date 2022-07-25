<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 June 11, 2017 5:09:26 PM
 */
interface AskScreenInterface extends ScreenInterface
{
    /**
     *
     * @param \Gems\Tracker\Token $token
     * @return array Added before all other parameters
     */
    public function getParameters(\Gems\Tracker\Token $token);

    /**
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of snippets or false to use original
     */
    public function getSnippets(\Gems\Tracker\Token $token);
}
