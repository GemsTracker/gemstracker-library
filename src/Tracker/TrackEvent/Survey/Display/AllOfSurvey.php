<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Display;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyDisplayEventInterface;
use Gems\Tracker\TrackEvent\TranslatableEventAbstract;

/**
 * Put the highest value first
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class AllOfSurvey extends TranslatableEventAbstract implements SurveyDisplayEventInterface
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(Token $token): array
    {
        return ['Tracker\\Answers\\SurveyAnswersModelSnippet'];
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Show all answers for this survey type');
    }
}
