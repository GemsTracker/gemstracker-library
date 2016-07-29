<?php
/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ToggleCharts.php 1835 2014-03-14 10:35:06Z matijsdejong $
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
class Gems_Event_Survey_Display_AllOfSurveyWithCharts extends \Gems_Event_Survey_Display_AllOfSurvey
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
        if (!is_array($snippets)) {
        	$snippets = array($snippets);
        }
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