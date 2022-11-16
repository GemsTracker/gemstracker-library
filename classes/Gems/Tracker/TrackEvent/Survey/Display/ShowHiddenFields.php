<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Display;

/**
 *
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ShowHiddenFields extends \MUtil\Translate\TranslateableAbstract
    implements \Gems\Event\SurveyDisplayEventInterface
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems\Tracker\Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems\Tracker\Token $token)
    {
        return 'Tracker\\Answers\\TrackAllAnswersModelSnippet';
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Display all answers, including those without a label.');
    }
}
