<?php

/**
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Completed;

/**
 * Displays the variables and their values to help create a new calculation
 *
 * To start a new calculation you need to know the exact name of the variables returned
 * by the survey source. This event will show this information and the values for each
 * token it finds.
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.1
 */
class CopyCodesToTrackFields extends \Gems_Event_EventCalculations implements \Gems_Event_SurveyCompletedEventInterface
{

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_("Copy answers to track fields with the same trackfield code");
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(\Gems_Tracker_Token $token)
    {
        if ($token->getReceptionCode()->isSuccess() && $token->isCompleted()) {
            $respTrack = $token->getRespondentTrack();
            $fields    = $respTrack->getCodeFields();
            $answers   = $token->getRawAnswers();
            $newFields = [];

            foreach ($fields as $code => $value) {
                if (isset($answers[$code]) && ($answers[$code] != $value)) {
                    $newFields[$code] = $answers[$code];
                }
            }

            if ($newFields) {
                // \MUtil_Echo::track($answers, $newFields);
                $respTrack->setFieldData($newFields);
            }
        }

        return false;
    }
}