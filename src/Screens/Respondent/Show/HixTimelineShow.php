<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Show;

use Gems\Screens\ShowScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 15-Apr-2020 15:42:00
 */
class HixTimelineShow extends GemsTimelineShow
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getParameters(): array
    {
        $params = parent::getParameters();

        return ['addCurrentParent' => false] + $params;
    }

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Timeline show respondent for HiX');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets(): array
    {
        return [
            // 'Generic\\ContentTitleSnippet',
            // 'Respondent\\MultiOrganizationTab',
            'Respondent\\RespondentMinimalDetailsSnippet',
            'Tracker\\AddTracksSnippet',
            'Respondent\\TrafficLightTokenSnippet',
        ];
    }
}
