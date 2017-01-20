<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Browse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Browse
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:08:36 PM
 */
interface BrowseScreenInterface extends ScreenInterface
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getAutofilterParameters();

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getAutofilterSnippets();

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStartSnippets();

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStopSnippets();

    /**
     *
     * @return array Added before all other parameters
     */
    public function getStartStopParameters();
}
