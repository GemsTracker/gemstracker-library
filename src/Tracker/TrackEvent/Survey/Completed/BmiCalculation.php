<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Completed;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use Gems\Tracker\TrackEvent\TranslatableEventAbstract;

/**
 * Calculates someone's BMI from LENGTH and WEIGHT.
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class BmiCalculation extends TranslatableEventAbstract implements SurveyCompletedEventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_("Bmi Calculation");
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
        $tokenAnswers = $token->getRawAnswers();

        if (isset($tokenAnswers['LENGTH'], $tokenAnswers['WEIGHT']) && $tokenAnswers['LENGTH'] && $tokenAnswers['WEIGHT']) {
            $length = $tokenAnswers['LENGTH'] / 100;
            $newValue = round($tokenAnswers['WEIGHT'] / ($length * $length),  2);

            if ($newValue !== $tokenAnswers['BMI']) {
                return array('BMI' => $newValue);
            }
        }

        return [];
    }
}
