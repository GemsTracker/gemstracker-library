<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class description of Survey
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Survey extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var array The gems survey data
     */
    private $_gemsSurvey;

    /**
     *
     * @var Gems_Tracker_SourceInterface
     */
    private $_source;

    /**
     *
     * @var string The id of the token
     */
    private $_surveyId;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Events
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
     * @var Gems_Tracker
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
    }

    /**
     * Makes sure the receptioncode data is part of the $this->_gemsData
     *
     * @param boolean $reload Optional parameter to force reload.
     */
    private function _ensureGroupData($reload = false)
    {
        if ($reload || (! isset($this->_gemsSurvey['ggp_id_group']))) {
            $sql  = "SELECT * FROM gems__groups WHERE ggp_id_group = ?";
            $code = $this->_gemsSurvey['gsu_id_primary_group'];

            if ($row = $this->db->fetchRow($sql, $code)) {
                $this->_gemsSurvey = $row + $this->_gemsSurvey;
            } else {
                $name = $this->getName();
                throw new Gems_Exception("Group code $code is missing for survey '$name'.");
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

            if (Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_gemsSurvey[$key] . ' => ' . $val . "\n";
                }
                MUtil_Echo::r($echo, 'Updated values for ' . $this->_surveyId);
            }

            if (! isset($values['gsu_changed'])) {
                $values['gsu_changed'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');
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
                    $values['gsu_created'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');
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
                $this->_gemsSurvey = array();
                $this->exists = false;
            }
        }

        return (boolean) $this->_gemsSurvey;
    }

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param Gems_Tracker_Token $token
     * @param string $language
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws Gems_Tracker_Source_SurveyNotFoundException
     */
    public function copyTokenToSource(Gems_Tracker_Token $token, $language)
    {
        $source = $this->getSource();
        return $source->copyTokenToSource($token, $language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * @param string $fieldName Name of answer field
     * @param Gems_Tracker_Token  $token Gems token object
     * @return MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName, Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->getAnswerDateTime($fieldName, $token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Returns a model for diaplying the answers to this survey in the requested language.
     *
     * @param string $language (ISO) language string
     * @return MUtil_Model_ModelAbstract
     */
    public function getAnswerModel($language)
    {
        $source = $this->getSource();
        return $source->getSurveyAnswerModel($this, $language, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * The time the survey was completed according to the source
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @return MUtil_Date date time or null
     */
    public function getCompletionTime(Gems_Tracker_Token $token)
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
     * @return int Gems group id for survey
     */
    public function getGroupId()
    {
        return $this->_gemsSurvey['gsu_id_primary_group'];
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
     * @param Gems_Tracker_Token $token Gems token object
     * @return MUtil_Date date time or null
     */
    public function getStartTime(Gems_Tracker_Token $token)
    {
        $source = $this->getSource();
        return $source->getStartTime($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     *
     * @return Gems_Tracker_Source_SourceInterface
     */
    public function getSource()
    {
        if (! $this->_source) {
            $this->_source = $this->tracker->getSource($this->_gemsSurvey['gsu_id_source']);

            if (! $this->_source) {
                throw new Gems_Exception('No source for exists for source ' . $this->_gemsSurvey['gsu_id_source'] . '.');
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
     * @return Gems_Event_SurveyBeforeAnsweringEventInterface event instance or null
     */
    public function getSurveyBeforeAnsweringEvent()
    {
        if ($this->_gemsSurvey['gsu_beforeanswering_event']) {
            return $event = $this->events->loadSurveyBeforeAnsweringEvent($this->_gemsSurvey['gsu_beforeanswering_event']);
        }
    }

    /**
     * Return the Survey Completed event
     *
     * @return Gems_Event_SurveyCompletedEventInterface event instance or null
     */
    public function getSurveyCompletedEvent()
    {
        if ($this->_gemsSurvey['gsu_completed_event']) {
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
     * @param Gems_Tracker_Token $token Gems token object
     * @param string $language
     * @return string The url to start the survey
     */
    public function getTokenUrl(Gems_Tracker_Token $token, $language)
    {
        $source = $this->getSource();
        return $source->getTokenUrl($token, $language, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id']);
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @return boolean
     */
    public function inSource(Gems_Tracker_Token $token)
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
        return (boolean) $this->_gemsSurvey['gsu_active'];
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
     * @param Gems_Tracker_Token $token Gems token object
     * @return boolean True if the token has completed
     */
    public function isCompleted(Gems_Tracker_Token $token)
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
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param Gems_Tracker_Token $token
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(Gems_Tracker_Token $token, $consentCode = null)
    {
        $source = $this->getSource();
        return $source->updateConsent($token, $this->_surveyId, $this->_gemsSurvey['gsu_surveyor_id'], $consentCode);
    }
}
