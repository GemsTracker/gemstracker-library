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
class DisplayVars extends \Gems\Event\EventCalculations
    implements \Gems\Event\SurveyCompletedEventInterface
{

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_("Echo the survey answers for testing");
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(\Gems\Tracker\Token $token)
    {
        $result = var_export($token->getRawAnswers(), true);
        \MUtil\EchoOut\EchoOut::r($result, $token->getTokenId());
        return false;
    }
}