<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Display;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyDisplayEventInterface;
use Gems\Tracker\TrackEvent\TranslatableEventAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ShowHiddenFields extends TranslatableEventAbstract
    implements SurveyDisplayEventInterface
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(Token $token): array
    {
        return ['Tracker\\Answers\\TrackAllAnswersModelSnippet'];
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Display all answers, including those without a label.');
    }
}
