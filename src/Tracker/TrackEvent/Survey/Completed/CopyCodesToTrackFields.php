<?php

/**
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Completed;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use Gems\Tracker\TrackEvent\TranslatableEventAbstract;

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
class CopyCodesToTrackFields extends TranslatableEventAbstract implements SurveyCompletedEventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_("Copy answers to track fields with the same trackfield code");
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(Token $token): array
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
                // \MUtil\EchoOut\EchoOut::track($answers, $newFields);
                $respTrack->setFieldData($newFields);
            }
        }

        return [];
    }
}