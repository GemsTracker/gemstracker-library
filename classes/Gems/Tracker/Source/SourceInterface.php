<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Interface description of SourceInterface for (external) survey sources.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
interface Gems_Tracker_Source_SourceInterface extends \MUtil_Registry_TargetInterface
{
    /**
     * Standard constructor for sources
     *
     * @param array $sourceData The information from gems__sources for this source.
     * @param \Zend_Db_Adapter_Abstract $gemsDb Do not want to copy db using registry because that is public and this should be private
     */
    public function __construct(array $sourceData, \Zend_Db_Adapter_Abstract $gemsDb);

    /**
     * Checks wether this particular source is active or not and should handle updating the gems-db
     * with the right information about this source
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return boolean
     */
    public function checkSourceActive($userId);

    /**
     * Survey source synchronization check function
     *
     * @param string $sourceSurveyId
     * @param int $surveyId
     * @param int $userId
     * @return mixed message string or array of messages
     */
    public function checkSurvey($sourceSurveyId, $surveyId, $userId);

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param \Gems_Tracker_Token $token
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems_Tracker_Source_SurveyNotFoundException
     */
    public function copyTokenToSource(\Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId = null);


    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName, \Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null);
    
    /**
     * Returns all the gemstracker names for attributes stored in source for a token
     * 
     * @return array
     */
    public function getAttributes();

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems_Tracker_Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Date date time or null
     */
    public function getCompletionTime(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null);

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getDatesList($language, $surveyId, $sourceSurveyId = null);

    /**
     *
     * @return int The source Id of this source
     */
    public function getId();

    /**
     * Returns an array of arrays with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Nested array
     */
    public function getQuestionInformation($language, $surveyId, $sourceSurveyId = null);

    /**
     * Returns an array containing fieldname => label for dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getQuestionList($language, $surveyId, $sourceSurveyId = null);

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId Gems Token Id
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null);

    /**
     * Returns the answers of multiple tokens in simple raw nested array format,
     * without value processing etc.
     *
     * Function may return more fields than just the answers.
     * The $filter param is an array of filters to apply to the selection, it has
     * some special formatting rules. The key is the db-field to filter on and the
     * value could be a value or an array of values to filter on.
     *
     * Special keys that should be mapped to the right field by the source are:
     *  respondentid
     *  organizationid
     *  consentcode
     *  token
     *
     * So a filter of [token]=>[abc-def][def-abc] will return the results for these two tokens
     * while a filter of [organizationid] => 70 will return all results for this organization.
     *
     * @param array $filter filter array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, $surveyId, $sourceSurveyId = null);

    /**
     * Returns the recordcount for a given filter
     *
     * @param array $filter filter array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int
     */
    public function getRawTokenAnswerRowsCount(array $filter, $surveyId, $sourceSurveyId = null);
    
    /**
     * Get the db adapter for this source
     *
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getSourceDatabase();

    /**
     * Gets the time the survey was started according to the source.
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems_Tracker_Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Date date time or null
     */
    public function getStartTime(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null);

    /**
     * Returns a model for the survey answers
     *
     * @param \Gems_Tracker_Survey $survey
     * @param string $language Optional (ISO) language string
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Model_ModelAbstract
     */
    public function getSurveyAnswerModel(\Gems_Tracker_Survey $survey, $language = null, $sourceSurveyId = null);

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId);

    /**
     * Returns true if a batch is set
     *
     * @return boolean
     * /
    public function hasBatch();

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null);

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null);

    /**
     * Set the batch to be used by this source
     *
     * Use $this->hasBatch to check for existence
     *
     * @param \Gems_Task_TaskRunnerBatch $batch
     * /
    public function setBatch(\Gems_Task_TaskRunnerBatch $batch);

    /**
     * Sets the answers passed on.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param $answers array Field => Value array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return true When answers changed
     */
    public function setRawTokenAnswers(\Gems_Tracker_Token $token, array $answers, $surveyId, $sourceSurveyId = null);

    /**
     * Sets the completion time.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param \Zend_Date|null $completionTime \Zend_Date or null
     * @param int $surveyId Gems Survey Id (actually required)
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setTokenCompletionTime(\Gems_Tracker_Token $token, $completionTime, $surveyId, $sourceSurveyId = null);

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param \Gems_Task_TaskRunnerBatch $batch
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     */
    public function synchronizeSurveyBatch(\Gems_Task_TaskRunnerBatch $batch, $userId);

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     * /
    public function synchronizeSurveys($userId);

    /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param \Gems_Tracker_Token $token
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null);
}
