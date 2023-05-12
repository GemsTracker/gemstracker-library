<?php

/**
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\BeforeAnswering;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\BeforeAnsweringAbstract;

/**
 * This event looks for a previous copy of a survey with the same code and copies
 * the answers for all fields starting with a prefix
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4 21-Mar-2018 19:49:43
 */
class FillTrackFieldAnswers extends BeforeAnsweringAbstract
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Copy track fields with the same trackfield code to answers');
    }

    /**
     * Perform the adding of values, usually the first set value is kept, later set values only overwrite if
     * you overwrite the $keepAnswer parameter of the output addCheckedValue function.
     *
     * @param Token $token
     */
    protected function processOutput(Token $token): void
    {
        $this->log("Setting track fields");

        $fields = $this->getTrackFieldValues($token->getRespondentTrack());
        // \MUtil\EchoOut\EchoOut::track($fields);

        $this->addCheckedArray($fields);
    }
}
