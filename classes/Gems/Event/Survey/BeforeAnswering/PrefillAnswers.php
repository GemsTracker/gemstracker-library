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
class PrefillAnswers extends \MUtil_Translate_TranslateableAbstract implements \Gems_Event_SurveyBeforeAnsweringEventInterface
{
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
     * Process the data and return the answers that should be filled in beforehand.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return array Containing the changed values
     */
    public function processTokenInsertion(\Gems_Tracker_Token $token)
    {
        $this->token = $token;

        if ($token->getReceptionCode()->isSuccess() && (!$token->isCompleted())) {
            // Read questioncodes
            $questions = $token->getSurvey()->getQuestionList(null);
            $fields    = [];
            $results   = [];

            // Check if they match a prefix schema
            foreach ($questions as $code => $text) {
                $upperField = strtoupper($code);
                $prefix     = substr($upperField, 0, $this->prefixLength);
                if (array_key_exists($prefix, $this->prefixes)) {
                    $fields[$prefix][$code] = substr($upperField, $this->prefixLength);
                }
            }

            if (count($fields) > 0) {
                foreach ($fields as $prefix => $requests) {
                    // Find which method to call
                    $method  = $this->prefixes[$prefix];
                    $results = $results + $this->$method($requests);
                }
            }

            return $results;
        }
    }

    /**
     * Tries to fulfill request to copy fields
     *
     * Copy fields will be fulfilled by searching the track in reverse
     *  - first to see if there is a field without the CP prefix
     *  - then if there are fields with the CP prefix
     * And only return the match when (in the end) ALL fields are found. If not
     * it will start again for the next answered token to the same survey, or
     * survey with the same code as the requesting token.
     *
     * @param array $requests
     * @return array
     */
    public function getCopyFields($requests)
    {
        $prefix     = 'CP';
        $token      = $this->token;
        $surveyCode = $token->getSurvey()->getCode();
        $surveyId   = $token->getSurveyId();

        $flipRequests = array_flip($requests);

        // Check from the last token back, we need to find the last answered token.
        // To be improved with a custom token loader that selects tokens answered before this one to prevent looping the complete track.
        $prev = $token->getRespondentTrack()->getLastToken();
        do {
            if ($prev->getReceptionCode()->isSuccess() && $prev->isCompleted()) {
                // Check first on survey id and when that does not work by code (if not empty).
                if ($prev->getSurveyId() === $surveyId || (!empty($surveyCode) && $prev->getSurvey()->getCode() === $surveyCode)) {
                    // @@TODO: Make case insensitive from here on, also change $check array to $requests
                    $answers   = $prev->getRawAnswers();
                    $answersUc = array_change_key_case($answers, CASE_UPPER);
                    $values    = array_intersect_key($answersUc, $flipRequests);
                    // Values now has the CP prefix requested answers that have a no-prefix match
                    // If this is not the complete set, we look for CP prefix matches to allow copy from start
                    if (count($values) !== count($requests)) {
                        $missing = array_diff_key($flipRequests, $values);
                        foreach ($missing as $key => $value) {
                            $prefixKey = $prefix . $key;
                            if (array_key_exists($prefixKey, $answersUc)) {
                                $values[$key] = $answersUc[$prefixKey];
                            }
                        }
                    }
                    $results = [];
                    foreach ($values as $key => $value) {
                        $newKey           = $flipRequests[$key];
                        $results[$newKey] = $value;
                    }
                    return $results;
                }
            }
        } while ($prev = $prev->getPreviousToken());

        return [];
    }

    /**
     * Tries to fulfill request to respondent fields
     *
     * @param array $requests
     * @return array
     */
    public function getRespondentFields($requests)
    {
        $token   = $this->token;
        $results = [];

        $respondent = $token->getRespondent();

        foreach ($requests as $original => $upperField) {
            switch ($upperField) {
                case 'AGE':
                    $results[$original] = $respondent->getAge();
                    break;

                case 'SEX':
                    $results[$original] = $respondent->getGender();
                    break;

                case 'BIRTHDATE':
                    $birthDate = $respondent->getBirthday();
                    if (!is_null($birthDate) && $birthDate instanceof \MUtil_Date) {
                        $birthDate          = $birthDate->get('yyyy-MM-dd');
                        $results[$original] = $birthDate;
                    }
                    break;

                default:
                    break;
            }
        }

        return $results;
    }

    /**
     * Tries to fulfill request to track fields
     *
     * @param array $requests
     * @return array
     */
    public function getTrackFields($requests)
    {
        $token   = $this->token;
        $results = [];

        // Read fieldcodes and convert to uppercase since requests are uppercase too
        $respondentTrack = $token->getRespondentTrack();
        $fieldCodes      = $respondentTrack->getCodeFields();
        $keysMixed       = array_keys($fieldCodes);
        $keysUpper       = array_change_key_case($fieldCodes, CASE_UPPER);
        $fieldCodesMap   = array_combine(array_keys($keysUpper), $keysMixed);

        foreach ($requests as $original => $upperField) {
            if (array_key_exists($upperField, $fieldCodesMap)) {
                $trackField         = $fieldCodesMap[$upperField];
                $results[$original] = $fieldCodes[$trackField];
            }
        }

        return $results;
    }

}
