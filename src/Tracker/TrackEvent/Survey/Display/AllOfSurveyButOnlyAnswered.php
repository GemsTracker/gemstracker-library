<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\Display
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Display;

use Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\Display
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class AllOfSurveyButOnlyAnswered extends OnlyAnswered
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(Token $token): array
    {
        $this->token = $token;

        $snippets[] = 'Tracker\\Answers\\SurveyAnswersModelSnippet';
        $snippets['answerFilter'] = $this;

        return $snippets;
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Show all answers for this survey type, but only the questions with an answer.');
    }
}