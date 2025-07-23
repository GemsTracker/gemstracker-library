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
class GemsTimelineShow extends ShowScreenAbstract
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getParameters(): array
    {
        return [
            '-run-once'      => 'openedRespondent',
            ];
    }

    /**
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Timeline show respondent');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets(): array
    {
        return [
            'Generic\\ContentTitleSnippet',
            'Respondent\\MultiOrganizationTab',
            'Respondent\\RespondentDetailsSnippet',
            'Tracker\\AddTracksSnippet',
            'Respondent\\TrafficLightTokenSnippet',
        ];
    }
}
