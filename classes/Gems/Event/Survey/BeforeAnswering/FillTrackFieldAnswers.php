<?php

/**
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\BeforeAnswering;

/**
 * This events look for a previous copy of a survey with the same code and copies
 * the answers for all fields starting with a prefix
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4 21-Mar-2018 19:49:43
 */
class FillTrackFieldAnswers extends \MUtil_Translate_TranslateableAbstract implements \Gems_Event_SurveyBeforeAnsweringEventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Copy track fields with the same trackfield code to answers');
    }

    /**
     * Process the data and return the answers that should be filled in beforehand.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return array Containing the changed values
     */
    public function processTokenInsertion(\Gems_Tracker_Token $token)
    {
        if ($token->getReceptionCode()->isSuccess() && (! $token->isCompleted())) {
            $respTrack = $token->getRespondentTrack();
            $fields    = $respTrack->getCodeFields();
            $questions = $token->getSurvey()->getQuestionList(null);
            $result    = [];

            foreach ($codes as $code => $value) {
                if (isset($questions[$code]) && ($questions[$code] != $value)) {
                    $result[$code] = $value;
                }
            }

            return $results;
        }
    }
}
