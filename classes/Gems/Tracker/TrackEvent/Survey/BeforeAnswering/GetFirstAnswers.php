<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\BeforeAnswering;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyBeforeAnsweringEventInterface;
use Gems\Tracker\TrackEvent\TranslatableEventAbstract;

/**
 * This event looks for a previous copy of the same
 *
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Nov 23, 2016 4:34:13 PM
 */
class GetFirstAnswers extends TranslatableEventAbstract implements SurveyBeforeAnsweringEventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Lookup answers in first instance of this survey in track.');
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

            $next = $token->getRespondentTrack()->getFirstToken();
            while ($next) {

                if ($next->getReceptionCode()->isSuccess() && $next->isCompleted()) {
                    // Check first on survey id and when that does not work by name.
                    if ($next->getSurveyId() == $surveyId) {
                        return $next->getRawAnswers();
                    }
                }
                $next = $next->getNextToken();
            }
        }
    }
}
