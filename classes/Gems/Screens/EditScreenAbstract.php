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
abstract class EditScreenAbstract extends \MUtil_Translate_TranslateableAbstract implements EditScreenInterface
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getCreateParameters()
    {
        return $this->getParameters();
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getEditParameters()
    {
        return $this->getParameters();
    }

    /**
     *
     * @return array Default added parameters
     */
    protected function getParameters()
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
     * @return mixed Something to display as label. Can be an MUtil_Html element
     */
    // public function getScreenLabel();
}
