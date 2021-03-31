<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Date\Period;

/**
 * Object representing a specific Survey
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Survey extends \Gems_Registry_TargetAbstract
{
    /**
     *
     * @var array The gems survey data
     */
    private $_gemsSurvey;

    /**
     *
     * @var \Gems_Tracker_SourceInterface
     */
    private $_source;

    /**
     *
     * @var string The id of the token
     */
    private $_surveyId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Events
     */
    protected $events;

    /**
     * True when the survey does exist.
     *
     * @var boolean
     */
    public $exists = true;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @param mixed $gemsSurveyData Token Id or array containing token record
     */
    public function __construct($gemsSurveyData)
    {
        if (is_array($gemsSurveyData)) {
            $this->_gemsSurvey = $gemsSurveyData;
            $this->_surveyId   = $gemsSurveyData['gsu_id_survey'];
        } else {
            $this->_surveyId = $gemsSurveyData;
        }

        // If loaded using tracker->getSurveyBySourceId the id can be negative if not found
        if ($this->_surveyId > 0) {
            $this->exists = true;
        } else {
            $this->exists = false;
        }
    }

    /**
     * Makes sure the group data is part of the $this->_gemsSurvey
     *
     * @param boolean $reload Optional parameter to force reload.
     */
    private function _ensureGroupData($reload = false)
    {
        if ($reload || (! isset($this->_gemsSurvey['ggp_id_group']))) {
            $sql  = "SELECT * FROM gems__groups WHERE ggp_id_group = ?";
            $code = $this->_gemsSurvey['gsu_id_primary_group'];

            if ($code) {
                $row = $this->db->fetchRow($sql, $code);
            } else {
                $row = false;
            }

            if ($row) {
                $this->_gemsSurvey = $row + $this->_gemsSurvey;
            } else {
                // Add default empty row
                $this->_gemsSurvey = array(
                    'ggp_id_group' => false,
                    'ggp_name' => '',
                    'ggp_description' => '',
                    'ggp_role' => 'respondent',
                    'ggp_group_active' => 0,
                    'ggp_respondent_members' => 1,
                    'ggp_staff_members' => 0) + $this->_gemsSurvey;
            }
        }
    }

    /**
     * Update the survey, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    private function _updateSurvey(array $values, $userId)
    {
        if ($this->tracker->filterChangesOnly($this->_gemsSurvey, $values)) {

            if (\Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $old = isset($this->_gemsSurvey[$key]) ? $this->_gemsSurvey[$key] : null;
                    $echo .= $key . ': ' . $old . ' => ' . $val . "\n";
                }
                \MUtil_Echo::r($echo, 'Updated values for ' . $this->_surveyId);
            }

            if (! isset($values['gsu_changed'])) {
                $values['gsu_changed'] = new \MUtil_Db_Expr_CurrentTimestamp();
            }
            if (! isset($values['gsu_changed_by'])) {
                $values['gsu_changed_by'] = $userId;
            }

            if ($this->exists) {
                // Update values in this object
                $this->_gemsSurvey = $values + $this->_gemsSurvey;

                // return 1;
                return $this->db->update('gems__surveys', $values, array('gsu_id_survey = ?' => $this->_surveyId));

            } else {
                if (! isset($values['gsu_created'])) {
                    $values['gsu_created'] = new \MUtil_Db_Expr_CurrentTimestamp();
                }
                if (! isset($values['gsu_created_by'])) {
                    $values['gsu_created_by'] = $userId;
                }

                // Update values in this object
                $this->_gemsSurvey = $values + $this->_gemsSurvey;

                // Remove the Gems survey id
                unset($this->_gemsSurvey['gsu_id_survey']);

                $this->_surveyId = $this->db->insert('gems__surveys', $this->_gemsSurvey);
                $this->_gemsSurvey['gsu_id_survey'] = $this->_surveyId;
                $this->exists = true;

                return 1;
            }

        } else {
            return 0;
        }
    }

    /**
     * Calculate a hash for this survey, taking into account the questions and answers
     *
     * @return string
     */
    public function calculateHash()
    {
        $answerModel = $this->getAnswerModel('en');
        foreach($answerModel->getItemsOrdered() as $item) {
                $result = $answerModel->get($item, ['label', 'type', 'multiOptions', 'parent_question', 'thClass', 'group', 'description']);
                if (array_key_exists('label', $result)) {
                    $items[$item] = $result;
                }
        }

        $hash = md5(serialize($items));

        return $hash;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db && (! $this->_gemsSurvey)) {
            $result = $this->db->fetchRow("SELECT * FROM gems__surveys WHERE gsu_id_survey = ?", $this->_surveyId);
            if ($result) {
                $this->_gemsSurvey = $result;
                $this->exists = true;
            } else {
                //Row not present, try with empty array? or should we throw an error?
                $this->_gemsSurvey = array(
                    'gsu_code'             => null,
                    'gsu_valid_for_length' => 6,
                    'gsu_valid_for_unit'   => 'M',
                    );
                $this->exists = false;
            }
        }

        return (boolean) $this->_gemsSurvey;
    }

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param \Gems_Tracker_Token $token
     * @param string $language
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems_Tracker_Source_SurveyNotFoundException
     */
    public function copyTokenToSource(\Gems_Tracker_Token $token, $language)
    {
        $source = $this->getSource();
        return $source->copyTokenToSource($token, $language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems_Tracker_Token  $token Gems token object
     * @return \MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName, \Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->getAnswerDateTime($fieldName, $token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of snippet names
     */
    public function getAnswerSnippetNames(\Gems_Tracker_Token $token)
    {
        if (isset($this->_gemsSurvey['gsu_display_event']) && $this->_gemsSurvey['gsu_display_event']) {
            $event = $this->events->loadSurveyDisplayEvent($this->_gemsSurvey['gsu_display_event']);

            return $event->getAnswerDisplaySnippets($token);
        }
    }

    /**
     * Returns a model for displaying the answers to this survey in the requested language.
     *
     * @param string $language (ISO) language string
     * @return \MUtil_Model_ModelAbstract
     */
    public function getAnswerModel($language)
    {
        $source = $this->getSource();
        return $source->getSurveyAnswerModel($this, $language, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     *
     * @return string Internal code of the survey
     */
    public function getCode()
    {
        return $this->_gemsSurvey['gsu_code'];
    }

    /**
     * The time the survey was completed according to the source
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return \MUtil_Date date time or null
     */
    public function getCompletionTime(\Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->getCompletionTime($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language (ISO) language string
     * @return array Returns an array of the strings datename => label
     */
    public function getDatesList($language)
    {
        $source = $this->getSource();
        return $source->getDatesList($language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     *
     * @return string Description of the survey
     */
    public function getDescription()
    {
        return $this->_gemsSurvey['gsu_survey_description'];
    }

    /**
     *
     * @return string Available languages of the survey
     */
    public function getAvailableLanguages()
    {
        return $this->_gemsSurvey['gsu_survey_languages'];
    }

    /**
     *
     * @return string Warning messages of the survey
     */
    public function getSurveyWarnings()
    {
        return $this->_gemsSurvey['gsu_survey_warnings'];
    }

    /**
     *
     * @return string The (manually entered) normal duration for taking this survey
     */
    public function getDuration()
    {
        return $this->_gemsSurvey['gsu_duration'];
    }

    /**
     *
     * @return string Export code of the survey
     */
    public function getExportCode()
    {
        return $this->_gemsSurvey['gsu_export_code'];
    }

    /**
     *
     * @return int Gems group id for survey
     */
    public function getGroupId()
    {
        return $this->_gemsSurvey['gsu_id_primary_group'];
    }

    /**
     *
     * @return string The hash of survey questions/answers
     */
    public function getHash()
    {
        return array_key_exists('gsu_hash', $this->_gemsSurvey) ? $this->_gemsSurvey['gsu_hash'] : null;
    }

    /**
     * Calculate the until date for single survey insertion
     *
     * @param \MUtil_Date $from
     * @return \MUtil_Date
     */
    public function getInsertDateUntil(\MUtil_Date $from)
    {
        return Period::applyPeriod(
                $from,
                $this->_gemsSurvey['gsu_valid_for_unit'],
                $this->_gemsSurvey['gsu_valid_for_length']
                );
    }

    /**
     *
     * @return string Name of the survey
     */
    public function getName()
    {
        return $this->_gemsSurvey['gsu_survey_name'];
    }

    /**
     * @return int
     */
    public function getMailCode()
    {
        return $this->_gemsSurvey['gsu_mail_code'];
    }

    /**
     * Returns an array of array with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @param string $language   (ISO) language string
     * @return array Nested array
     */
    public function getQuestionInformation($language)
    {
        return $this->getSource()->getQuestionInformation($language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns a fieldlist with the field names as key and labels as array.
     *
     * @param string $language (ISO) language string
     * @return array of fieldname => label type
     */
    public function getQuestionList($language)
    {
        return $this->getSource()->getQuestionList($language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId Gems Token Id
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow($tokenId)
    {
        $source = $this->getSource();
        return $source->getRawTokenAnswerRow($tokenId, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns the answers of multiple tokens in simple raw nested array format,
     * without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param array $filter XXX
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows($filter = array())
    {
        $source = $this->getSource();
        return $source->getRawTokenAnswerRows((array) $filter, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns the number of answers of multiple tokens
     *
     * @param array $filter XXX
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRowsCount($filter = array())
    {
        $source = $this->getSource();
        return $source->getRawTokenAnswerRowsCount((array) $filter, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Retrieve the name of the resultfield
     *
     * The resultfield should be present in this surveys answers.
     *
     * @return string
     */
    public function getResultField() {
        return $this->_gemsSurvey['gsu_result_field'];
    }

    /**
     * The time the survey was started according to the source
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return \MUtil_Date date time or null
     */
    public function getStartTime(\Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->getStartTime($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     *
     * @return \Gems_Tracker_Source_SourceInterface
     */
    public function getSource()
    {
        if (! $this->_source && isset($this->_gemsSurvey['gsu_id_source']) && $this->_gemsSurvey['gsu_id_source']) {
            $this->_source = $this->tracker->getSource($this->_gemsSurvey['gsu_id_source']);

            if (! $this->_source) {
                throw new \Gems_Exception('No source for exists for source ' . $this->_gemsSurvey['gsu_id_source'] . '.');
            }
        }

        return $this->_source;
    }

    /**
     *
     * @return int Gems survey ID
     */
    public function getSourceSurveyId()
    {
        return $this->_gemsSurvey['gsu_surveyor_id'];
    }

    /**
     *
     * @return string Survey status
     */
    public function getStatus()
    {
        return $this->_gemsSurvey['gsu_status'];
    }

    /**
     * Return the Survey Before Answering event (if any)
     *
     * @return \Gems_Event_SurveyBeforeAnsweringEventInterface event instance or null
     */
    public function getSurveyBeforeAnsweringEvent()
    {
        if (isset($this->_gemsSurvey['gsu_beforeanswering_event']) && $this->_gemsSurvey['gsu_beforeanswering_event']) {
            return $event = $this->events->loadSurveyBeforeAnsweringEvent($this->_gemsSurvey['gsu_beforeanswering_event']);
        }
    }

    /**
     * Return the Survey Completed event
     *
     * @return \Gems_Event_SurveyCompletedEventInterface event instance or null
     */
    public function getSurveyCompletedEvent()
    {
        if (isset($this->_gemsSurvey['gsu_completed_event']) && $this->_gemsSurvey['gsu_completed_event']) {
            return $event = $this->events->loadSurveyCompletionEvent($this->_gemsSurvey['gsu_completed_event']);
        }
    }

    /**
     *
     * @return int Gems survey ID
     */
    public function getSurveyId()
    {
        return $this->_surveyId;
    }

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param string $language
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems_Tracker_Token $token, $language)
    {
        $source = $this->getSource();
        return $source->getTokenUrl($token, $language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     *
     * @return boolean True if the survey has a pdf
     */
    public function hasPdf()
    {
        return (boolean) isset($this->_gemsSurvey['gsu_survey_pdf']) && $this->_gemsSurvey['gsu_survey_pdf'];
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return boolean
     */
    public function inSource(\Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->inSource($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     *
     * @return boolean True if the survey is active
     */
    public function isActive()
    {
        return (boolean) isset($this->_gemsSurvey['gsu_active']) && $this->_gemsSurvey['gsu_active'];
    }

    /**
     *
     * @return boolean True if the survey is active in the source
     */
    public function isActiveInSource()
    {
        return (boolean) $this->_gemsSurvey['gsu_surveyor_active'];
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->isCompleted($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Should this survey be filled in by staff members.
     *
     * @return boolean
     */
    public function isTakenByStaff()
    {
        if (! isset($this->_gemsSurvey['ggp_staff_members'])) {
            $this->_ensureGroupData();
        }

        return (boolean) $this->_gemsSurvey['ggp_staff_members'];
    }

    /**
     * Update the survey, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function saveSurvey(array $values, $userId)
    {
        // Keep the pattern of this object identical to that of others,
        // i.e. use an _update function
        return $this->_updateSurvey($values, $userId);
    }

    /**
     *
     * @param string $hash The hash for this survey
     * @param int $userId The current user
     */
    public function setHash($hash, $userId)
    {
        if ($this->getHash() !== $hash && array_key_exists('gsu_hash', $this->_gemsSurvey)) {
            $values['gsu_hash'] = $hash;
            $this->_updateSurvey($values, $userId);
        }
    }

    /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param \Gems_Tracker_Token $token
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(\Gems_Tracker_Token $token, $consentCode = null)
    {
        $source = $this->getSource();
        return $source->updateConsent($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id'], $consentCode);
    }
}
