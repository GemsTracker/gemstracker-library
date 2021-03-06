<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Show;

use Gems\Screens\ShowScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:52:09 PM
 */
class GemsRoundTabsShow extends ShowScreenAbstract
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getParameters()
    {
        return [
            'baseUrl'        => 'getItemUrlArray',
            'forOtherOrgs'   => 'getOtherOrgs',
            'onclick'        => 'getEditLink',
            '-run-once'      => 'openedRespondent',
            ];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('Rounds as tabs show');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets()
    {
        return [
            'Generic_ContentTitleSnippet',
            'Respondent\\RespondentDetailsSnippet',
            'Respondent\\RoundsTabsSnippet',
            'Token\\RoundTokenSnippet',
        ];
    }
}
