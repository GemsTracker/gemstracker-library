<?php
/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Display;

use Gems\Tracker\Token;

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
class AllOfSurveyWithCharts extends AllOfSurvey
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(Token $token): array
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
    public function getEventName(): string
    {
        return sprintf($this->translator->_('%s with chart'), parent::getEventName());
    }
}