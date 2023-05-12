<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\BeforeAnswering;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyBeforeAnsweringEventInterface;
use Gems\Tracker\TrackEvent\TranslatableEventAbstract;

/**
 * This events look for a previous copy of the same
 *
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class GetPreviousAnswers extends TranslatableEventAbstract implements SurveyBeforeAnsweringEventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Lookup answers in previous instance of survey in track.');
    }

    /**
     * Process the data and return the answers that should be filled in beforehand.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenInsertion(Token $token): array
    {
        if ($token->getReceptionCode()->isSuccess() && (! $token->isCompleted())) {
            // Preparation for a more general object class
            $surveyId   = $token->getSurveyId();

            $prev = $token;
            while ($prev = $prev->getPreviousToken()) {

                if ($prev->getReceptionCode()->isSuccess() && $prev->isCompleted()) {
                    // Check first on survey id and when that does not work by name.
                    if ($prev->getSurveyId() == $surveyId) {
                        return $prev->getRawAnswers();
                    }
                }
            }
        }
        return [];
    }
}
