<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ShowHiddenFields.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Event_Survey_Display_ShowHiddenFields extends \MUtil_Translate_TranslateableAbstract
    implements \Gems_Event_SurveyDisplayEventInterface
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems_Tracker_Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems_Tracker_Token $token)
    {
        return 'Tracker_Answers_TrackAllAnswersModelSnippet';
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
