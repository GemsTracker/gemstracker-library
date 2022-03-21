<?php

/**
 * @package    Gems
 * @subpackage Events
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\BeforeAnswering;

/**
 * This events look for a previous copy of a survey with the same code and copies
 * the answers for all fields starting with a prefix
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class PrefillAnswers extends FillBirthDayGender
{
    /**
     * @var string[] 
     */
    protected $prefixes     = [
        'TF' => 'getTrackFields',
        'CP' => 'getCopyFields',
        'RD' => 'getRespondentFields'
    ];
    protected $prefixLength = 2;

    /**
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Fill survey question when code starts with: TF for trackfield, CP for survey or RD for Respondent (only age/sex/birthdate)');
    }

    /**
     * Perform the adding of values, usually the first set value is kept, later set values only overwrite if
     * you overwrite the $keepAnswer parameter of the output addCheckedValue function.
     *
     * @param \Gems_Tracker_Token $token
     */
    protected function processOutput(\Gems_Tracker_Token $token)
    {
        // TF TrackField part
        $this->log("Setting TF track fields");
        $fields = $this->getTrackFieldValues($token->getRespondentTrack());
        foreach ($fields as $code => $value) {
            $this->addCheckedValue('tf' . $code, $value);
        }

        // CP survey answer codes
        $this->log("Filling CP previous answers");
        $previous = $this->getPreviousToken($token);
        if ($previous) {
            foreach ($previous->getRawAnswers() as $answer => $value) {
                $this->addCheckedValue('cp' . $answer, $value);
            }
        } else {
            $this->log("No previous answers found");
        }
        
        // RD respondent part
        $this->log("Filling rd respondent fields");
        $respondent = $token->getRespondent();
        $this->addCheckedValue('rdAge', $respondent->getAge());
        $this->addCheckedValue('rdSex', $respondent->getGender());
        $birthDate = $respondent->getBirthday();
        if (!is_null($birthDate) && $birthDate instanceof \MUtil_Date) {
            $this->addCheckedValue('rdBirthDate', $birthDate->get('yyyy-MM-dd'));
        }
        
        parent::processOutput($token);
    }
}
