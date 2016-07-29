<?php
/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display survey answers with a toggle for full or compact view and add a barchart
 * for each SCORE element found in the survey.
 *
 * @package    Gems
 * @subpackage attribute
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Event_Survey_Display_ToggleCharts extends \Gems_Event_Survey_Display_CompactFullToggle
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems_Tracker_Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems_Tracker_Token $token)
    {
        $snippets = parent::getAnswerDisplaySnippets($token);

        // Add the ScoreChartsSnippet
        $snippets[] = 'Survey_Display_ScoreChartsSnippet';
        return $snippets;
    }
    
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return sprintf($this->_('%s with chart'), parent::getEventName());
    }
}