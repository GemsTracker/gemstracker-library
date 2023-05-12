<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Edit
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Edit
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:08:57 PM
 */
interface EditScreenInterface extends ScreenInterface
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getCreateParameters();

    /**
     *
     * @return array Added before all other parameters
     */
    public function getEditParameters();

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets();
}
