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
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:15:21 PM
 */
class WithAddressBrowse extends BrowseScreenAbstract
{
    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getAutofilterSnippets()
    {
        return ['Respondent\\RespondentTableSnippet'];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('Browse with address');
    }
}
