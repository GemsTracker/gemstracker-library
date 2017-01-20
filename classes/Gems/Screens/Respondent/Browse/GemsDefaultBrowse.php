<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Browse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Browse;

use Gems\Screens\BrowseScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Browse
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:52:09 PM
 */
class GemsDefaultBrowse extends BrowseScreenAbstract
{
    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getAutofilterSnippets()
    {
        return [
            'Gems\\Snippets\\Respondent\\RespondentTableSnippet',
            ];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an MUtil_Html element
     */
    public function getScreenLabel()
    {
        return $this->_('(default Gems table display)');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStartSnippets()
    {
        return [
            'Gems\\Snippets\\Generic\\ContentTitleSnippet',
            'Gems\\Snippets\\Respondent\\RespondentSearchSnippet',
        ];
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStopSnippets()
    {
        return [];
    }
}
