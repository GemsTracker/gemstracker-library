<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 18-Mar-2019 16:37:08
 */
interface SubscribeScreenInterface extends ScreenInterface
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getSubscribeParameters();

    /**
     *
     * @return array Of snippets
     */
    public function getSubscribeSnippets();
}
