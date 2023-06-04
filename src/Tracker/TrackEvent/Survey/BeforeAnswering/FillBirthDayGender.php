<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\BeforeAnswering;

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\BeforeAnsweringAbstract;

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @license    New BSD License
 * @since      Class available since version 1.9.2
 */
class FillBirthDayGender extends BeforeAnsweringAbstract
{
    /**
     * @inheritDoc
     */
    public function getEventName(): string
    {
        return $this->translator->_('Copy gtAgeYears, gtAgeMonths, gtGender and gtRespondentNr.');
    }

    /**
     * Perform the adding of values, usually the first set value is kept, later set values only overwrite if
     * you overwrite the $keepAnswer parameter of the output addCheckedValue function.
     *
     * @param \Gems\Tracker\Token $token
     */
    protected function processOutput(Token $token): void
    {
        $this->log("Filling gt respondent fields");

        $respondent = $token->getRespondent();
        $this->addCheckedValue('gtRespondentNr', $respondent->getPatientNumber());

        $birthDay   = $respondent->getBirthday();
        if ($birthDay instanceof \DateTimeInterface) {
            $now  = new \DateTimeImmutable();

            $interval = $birthDay->diff($now);
            $this->addCheckedValue('gtAgeYears', $interval->y);
            $this->addCheckedValue('gtAgeMonths', ($interval->y * 12) + $interval->m);
        } else {
            $this->log("No birthday set for respondent.");
        }
        
        $gender = $respondent->getGender();
        if ($gender) {
            $this->addCheckedValue('gtGender', $gender);
        } else {
            $this->log("No gender set for respondent.");
        }   
    }
}