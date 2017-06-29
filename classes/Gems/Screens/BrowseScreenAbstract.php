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
abstract class BrowseScreenAbstract extends \MUtil_Translate_TranslateableAbstract implements BrowseScreenInterface
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getAutofilterParameters()
    {
        return [
            'columns'    => null,
            'useColumns' => false,
            ];
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getAutofilterSnippets()
    {
        return false;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    // public function getScreenLabel();

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStartSnippets()
    {
        return ['Generic\\ContentTitleSnippet', 'Respondent\\RespondentSearchSnippet'];
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStopSnippets()
    {
        return false;
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getStartStopParameters()
    {
        return [];
    }
}
