<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Calculates someones BMI from LENGTH and WEIGHT.
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Event_Survey_Completed_BmiCalculation extends \MUtil_Translate_TranslateableAbstract
        implements \Gems_Event_SurveyCompletedEventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_("Bmi Calculation");
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
        $tokenAnswers = $token->getRawAnswers();

        if (isset($tokenAnswers['LENGTH'], $tokenAnswers['WEIGHT']) && $tokenAnswers['LENGTH'] && $tokenAnswers['WEIGHT']) {
            $length = $tokenAnswers['LENGTH'] / 100;
            $newValue = round($tokenAnswers['WEIGHT'] / ($length * $length),  2);

            if ($newValue !== $tokenAnswers['BMI']) {
                return array('BMI' => $newValue);
            }
        }

        return false;
    }
}
