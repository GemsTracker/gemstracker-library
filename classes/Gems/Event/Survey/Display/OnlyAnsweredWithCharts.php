<?php
/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Display;

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
class OnlyAnsweredWithCharts extends \Gems\Event\Survey\Display\OnlyAnswered
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems\Tracker\Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems\Tracker\Token $token)
    {
        $snippets = parent::getAnswerDisplaySnippets($token);
        if (!is_array($snippets)) {
        	$snippets = array($snippets);
        }
        // Add the ScoreChartsSnippet
        $snippets[] = 'Survey\\Display\\ScoreChartsSnippet';
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