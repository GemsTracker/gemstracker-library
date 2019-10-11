<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 11-Oct-2019 16:14:26
 */
interface ConsentInterface extends EditScreenInterface
{
    /**
     *
     * @return array Of snippets or false to use original for consent editing
     */
    public function getConsentSnippets();

    /**
     *
     * @return array Added before all other parameters
     */
    public function getConsentParameters();
}
