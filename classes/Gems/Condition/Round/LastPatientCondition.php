<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Condition\Round
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Condition\Round;

use Gems\Condition\Round\LastAnswerCondition;

/**
 * @package    Gems
 * @subpackage Condition\Round
 * @since      Class available since version 1.0
 */
class LastPatientCondition extends LastAnswerCondition
{
    public function getDisplayText()
    {
        return $this->_('Last answer for respondent to question `%s`');
    }

    public function getHelp()
    {
        return $this->_("Look back from the current survey and find the first answered question for the respondent");
    }

    public function getLastAnswer($questionCode, $token)
    {
        $answer         = 'N/A';    // Default if we find no answer
        $date           = $token->getValidFrom();
        $questionCodeUc = strtoupper($questionCode);

        if (! $date) {
            $date = new \MUtil_Date();
        }

        $select = $this->tracker->getTokenSelect();
        $select->andRespondents()
            ->andRespondentOrganizations()
            ->forRespondent($token->getRespondentId(), $token->getOrganizationId())
            ->andReceptionCodes()
            ->onlySucces()
            ->onlyCompleted()
            ->forWhere("gto_completion_time < ?", $date->toString(\Gems_Tracker::DB_DATETIME_FORMAT))
            ->order('gto_completion_time DESC');

        foreach ($select->fetchAll() as $tokenData) {
            $prev = $this->tracker->getToken($tokenData);
            if (! ($prev && $prev->getReceptionCode()->isSuccess() && $prev->isCompleted())) {
                continue;
            }

            $answers   = $prev->getRawAnswers();
            $answersUc = array_change_key_case($answers, CASE_UPPER);

            if (array_key_exists($questionCodeUc, $answersUc)) {
                $answer = $answersUc[$questionCodeUc];
                break;
            }
        }

        return $answer;
    }

    public function getName()
    {
        return $this->_('Previous answer for respondent');
    }
}