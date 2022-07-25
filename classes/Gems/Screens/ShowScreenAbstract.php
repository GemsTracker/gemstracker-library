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
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:07:33 PM
 */
abstract class ShowScreenAbstract extends \MUtil\Translate\TranslateableAbstract implements ShowScreenInterface
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getParameters()
    {
        return [];
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets()
    {
        return false;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // public function getScreenLabel();
}
