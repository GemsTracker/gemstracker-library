<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Event;

use Gems\Tracker\Mock\TokenReadonly;

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\BeforeAnswering
 * @license    New BSD License
 * @since      Class available since version 1.9.2
 */
abstract class BeforeAnsweringAbstract extends \MUtil\Translate\TranslateableAbstract 
    implements \Gems\Event\SurveyBeforeAnsweringEventInterface
{
    /**
     * @var FIELDNAME in ucase => fieldname
     */
    private $_answerKeyMap;

    /**
     * @var array fieldname => value or null
     */
    private $_answers;

    /**
     * @var array Array of actions for optional log
     */
    private $_log = [];

    /**
     * For new values
     *
     * @var fieldname => value
     */
    private $_output;

    /**
     * @var \Gems_Locale
     */
    protected $locale;

    /**
     * @var bool When true the answer fields are mapped case sensitive (default is not)
     */
    protected $mapKeysCaseSensitive = false;

    /**
     * Add a whole array in one go
     *
     * @param array $values A scalar key identifying an answer (case insensitive) => The value to set
     * @param boolean $keepAnswer Do not overwrite an existing answer
     */
    protected function addCheckedArray(array $values, $keepAnswer = true)
    {
        // \MUtil\EchoOut\EchoOut::track($values);
        foreach ($values as $key => $value) {
            if ($value) {
                $this->addCheckedValue($key, $value, $keepAnswer);
            }
        }
    }

    /**
     * @param scalar $key A scalar key identifying an answer (case insensitive)
     * @param mixed $value The value to set
     * @param boolean $keepAnswer Do not overwrite an existing answer
     */
    protected function addCheckedValue($key, $value, $keepAnswer = true)
    {
        if (! (strlen($value) && $key && is_scalar($key))) {
            // Do not set if no value or no key or not a correct key
            return;
        }

        $akey = $this->checkOutputKey($key, $keepAnswer, $value, true);
        if ($akey) {
            $this->_output[$akey] = $value;
            $this->log("Set $key to $value!");
        }
    }

    /**
     * @param $key
     * @param boolean $keepAnswer Do not set when overwriting an existing answer
     * @return bool True if the key can still be set
     */
    protected function canBeSet($key, $keepAnswer = true)
    {
        $akey = $this->checkOutputKey($key, $keepAnswer);
        if ($akey) {
            return ! isset($this->_output[$akey]);
        }
        return false;
    }

    /**
     * Check a key for adding output to the answers
     *
     * @param scalar $key
     * @param boolean $keepAnswer Do not overwrite an existing answer
     * @param mixed $value The value (for logging purposes only)
     * @param boolean $log Write refusal to log file
     * @return false|string The answer key to use for adding or false
     */
    protected function checkOutputKey($key, $keepAnswer = true, $value = null, $log = false)
    {
        $ukey = $this->mapKeysCaseSensitive ? $key : strtoupper($key);
        if (! isset($this->_answerKeyMap[$ukey])) {
            if ($log) {
                $this->log("Blocked setting of $key to $value, $key not in output.");
            }
            // Cannot be added the answer key does not exist 
            return false;
        }

        $akey = $this->_answerKeyMap[$ukey];
        if ($keepAnswer && isset($this->_answers[$akey]) && $this->_answers[$akey]) {
            if ($log) {
                $val = $this->_answers[$akey];
                $this->log("Blocked setting of $key to $value, $key already set in output to $val.");
            }
            // Do not overwrite an existing answer value
            return false;
        }

        return $akey;
    }

    /**
     * @param \Gems\Tracker\Token $token
     * @return array
     */
    protected function getAnswersFieldsFromSurvey(\Gems\Tracker\Token $token)
    {
        return array_fill_keys(array_keys($token->getSurvey()->getQuestionList($this->locale)), null);
    }

    /**
     * The final output
     *
     * @return array fieldName => value
     */
    protected function getOutput()
    {
        // Wo do not want to output these ever.
        unset($this->_output['datestamp'], $this->_output['id'], $this->_output['startdate'], $this->_output['startlanguage'], $this->_output['submitdate'], $this->_output['token']);

        // \MUtil\EchoOut\EchoOut::track($this->_output);
        return $this->_output;
    }

    /**
     * If a previous token is returned, use it to fill answers
     * 
     * You can overrule this code, but the default is to check for a survey code and look
     * for a survey with the same code if it exists. Otherwise it has to be the same survey.
     *
     * @param \Gems\Tracker\Token $token
     * @return string|\Gems\Tracker\Token Token (id) or null
     */
    public function getPreviousToken(\Gems\Tracker\Token $token)
    {
        if ($token->getSurvey()->getCode()) {
            return $this->getPreviousTokenByCode($token);
        } else {
            return $this->getPreviousTokenBySurvey($token);
        }
    }

    /**
     * Return the previous token by looking at the survey code of the current token
     *
     * @param \Gems\Tracker\Token $token
     * @return string|\Gems\Tracker\Token Token (id) or null
     */
    public function getPreviousTokenByCode(\Gems\Tracker\Token $token)
    {
        $code = $token->getSurvey()->getCode();

        $prev = $token;
        while ($prev = $prev->getPreviousToken()) {

            if ($prev->getReceptionCode()->isSuccess() && $prev->isCompleted()) {
                // Check first on survey id and when that does not work by name.
                if ($prev->getSurvey()->getCode() == $code) {
                    return $prev;
                }
            }
        }
    }

    /**
     * Return the answered previous token of the same survey
     *
     * @param \Gems\Tracker\Token $token
     * @return string|\Gems\Tracker\Token Token (id) or null
     */
    public function getPreviousTokenBySurvey(\Gems\Tracker\Token $token)
    {
        $surveyId   = $token->getSurveyId();

        $prev = $token;
        while ($prev = $prev->getPreviousToken()) {

            if (($prev->getSurveyId() == $surveyId) && $prev->getReceptionCode()->isSuccess() && $prev->isCompleted()) {
                return $prev;
            }
        }
    }

    /**
     * Returns the track field VALUES as apposed to the DISPLAY VALUES returned by $respondentTrack->getCodeFields()
     *
     * @param array $requests
     * @return array
     */
    public function getTrackFieldValues(\Gems\Tracker\RespondentTrack $respondentTrack)
    {
        $fieldCode2Label = $respondentTrack->getCodeFields();
        $rawFieldData    = $respondentTrack->getFieldData();    // Date (time) fields are unprocessed here
        $results         = [];

        foreach ($fieldCode2Label as $key => $label) {
            if (array_key_exists($key, $rawFieldData)) {
                $value = $rawFieldData[$key];
                
                // If it is a date(/time) field export it in ISO format
                if ($rawFieldData[$key] instanceof \MUtil\Date) {
                    $value = $value->toString('yyyy-MM-dd HH:mm:ss');
                }
                $results[$key] = $value;
            }
        }

        return $results;
    }
    
    /**
     * @param $key The key of a set value
     * @return false|mixed The value set or used in answers
     */
    protected function getValue($key)
    {
        $akey = $this->checkOutputKey($key);
        if ($akey && isset($this->_output[$akey])) {
            return $this->_output[$akey];
        }

        if (isset($this->_answers[$akey])) {
            return $this->_answers[$akey];
        }

        return false;
    }

    /**
     * @param $line
     */
    protected function log($line)
    {
        $this->_log[] = $line;
    }

    /**
     * @param \Gems\Tracker\Token $token
     */
    protected function prepareOutput(\Gems\Tracker\Token $token)
    {
        $this->_answers = $token->getRawAnswers();
        if (! $this->_answers) {
            $this->_answers = $this->getAnswersFieldsFromSurvey($token);
        }

        $akeys = array_keys($this->_answers);
        if ($this->mapKeysCaseSensitive) {
            $this->_answerKeyMap = array_combine($akeys, $akeys);
        } else {
            $this->_answerKeyMap = array_combine(array_map('strtoupper', $akeys), $akeys);
        }
        // \MUtil_echo::track(array_filter($this->_answers));

        $this->_log    = [];
        $this->_output = [];
    }

    /**
     * Perform the adding of values, usually the first set value is kept, later set values only overwrite if
     * you overwrite the $keepAnswer parameter of the output addCheckedValue function.
     *
     * @param \Gems\Tracker\Token $token
     */
    abstract protected function processOutput(\Gems\Tracker\Token $token);

    /**
     * Process the data and return the answers that should be filled in beforehand.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenInsertion(\Gems\Tracker\Token $token)
    {
        // Do nothing when completed or deleted
        if ($token->isCompleted() || (! $token->getReceptionCode()->isSuccess())) {
            return [];
        }

        $this->prepareOutput($token);

        $this->processOutput($token);

        if ($this->_log && $token instanceof TokenReadonly) {
            $token->addLog($this->_log);
        }

        return $this->getOutput();
    }
}
