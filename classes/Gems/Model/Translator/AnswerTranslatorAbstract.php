<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 24-apr-2014 16:08:57
 */
abstract class Gems_Model_Translator_AnswerTranslatorAbstract extends \Gems_Model_ModelTranslatorAbstract
{
    /**
     * Constant for creating an extra token when a token was already filled in.
     */
    const TOKEN_DOUBLE = 'double';

    /**
     * Constant for generating an error when a token does not exist or is already filled in.
     */
    const TOKEN_ERROR = 'error';

    /**
     * Constant for creating a new token, while disabling the existing token
     */
    const TOKEN_OVERWRITE = 'overwrite';

    /**
     * Constant for creating a new token, while disabling the existing token
     */
    const TOKEN_SKIP = 'skip';

    /**
     * One of the TOKEN_ constants telling what to do when no token exists
     *
     * @var string
     */
    protected $_noToken = self::TOKEN_ERROR;

    /**
     *
     * @var boolean
     */
    protected $_skipUnknownPatients = false;

    /**
     * The Gems id of the survey to import to
     *
     * @var int
     */
    protected $_surveyId;

    /**
     * One of the TOKEN_ constants telling what to do when the token is completed
     *
     * @var string
     */
    protected $_tokenCompleted = self::TOKEN_ERROR;

    /**
     * The id of the track to import to or null
     *
     * @var int
     */
    protected $_trackId;
    
    /**
     * Datetime import formats
     *
     * @var array
     */
    public $datetimeFormats = array(
        \Zend_Date::ISO_8601, 
        'yyyy-MM-dd HH:mm:ss', 
        'yyyy-MM-dd'
        );
    
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The name of the field to (temporarily) store the organization id in
     *
     * @var string
     */
    protected $orgIdField = 'organization_id';

    /**
     * Extra values the origanization id field accepts
     *
     * @var array
     */
    protected $orgTranslations;

    /**
     * The name of the field to (temporarily) store the patient nr in
     *
     * @var string
     */
    protected $patientNrField = 'patient_id';

    /**
     * The task used for import
     *
     * @var string
     */
    protected $saveTask = 'Import_SaveAnswerTask';

    /**
     * Add the current row to a (possibly separate) batch that does the importing.
     *
     * @param \MUtil_Task_TaskBatch $importBatch The import batch to impor this row into
     * @param string $key The current iterator key
     * @param array $row translated and validated row
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function addSaveTask(\MUtil_Task_TaskBatch $importBatch, $key, array $row)
    {
        $importBatch->setTask(
                $this->saveTask,
                'import-' . $key,
                $row,
                $this->getNoToken(),
                $this->getTokenCompleted()
                );

        return $this;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->orgTranslations = $this->db->fetchPairs('
            SELECT gor_provider_id, gor_id_organization
                FROM gems__organizations
                WHERE gor_provider_id IS NOT NULL
                ORDER BY gor_provider_id');

        $this->orgTranslations = $this->orgTranslations + $this->db->fetchPairs('
            SELECT gor_code, gor_id_organization
                FROM gems__organizations
                WHERE gor_code IS NOT NULL
                ORDER BY gor_id_organization');
    }

    /**
     * Check should a patient be imported
     *
     * @param string $patientNr
     * @param int $orgId
     * @return boolean
     */
    protected function checkPatient($patientNr, $orgId)
    {
        if (! ($patientNr && $orgId)) {
            return false;
        }

        $select = $this->db->select();
        $select->from('gems__respondent2org', array('gr2o_id_user'))
            ->where('gr2o_patient_nr = ?', $patientNr)
            ->where('gr2o_id_organization = ?', $orgId);

        return $this->db->fetchOne($select);
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->db instanceof \Zend_Db_Adapter_Abstract) &&
            parent::checkRegistryRequestsAnswers();
    }

    /**
     * If the token can be created find the respondent track for the token
     *
     * @param array $row
     * @return int|null
     */
    abstract protected function findRespondentTrackFor(array $row);

    /**
     * Find the token id using the passed row data and
     * the other translator parameters.
     *
     * @param array $row
     * @return string|null
     */
    abstract protected function findTokenFor(array $row);

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        $this->_targetModel->set('completion_date', 'label', $this->_('Completion date'),
                'order', 9,
                'type', \MUtil_Model::TYPE_DATETIME
                );

        $fieldList = array('completion_date' => 'completion_date');

        foreach ($this->_targetModel->getCol('survey_question') as $name => $use) {
            if ($use) {
                $fieldList[$name] = $name;
            }
        }

        return $fieldList;
    }

    /**
     * Get the treatment when no token exists
     *
     * @return string One of the TOKEN_ constants.
     */
    public function getNoToken()
    {
        return $this->_noToken;
    }

    /**
     * Get the error message for when no token exists
     *
     * @return string
     */
    public function getNoTokenError(array $row, $key)
    {
        return sprintf(
                $this->_('No token found for %s.'),
                implode(" / ", $row)
                );
    }

    /**
     * Get the id of the survey to import to
     *
     * @return int $surveyId
     */
    public function getSurveyId()
    {
        return $this->_surveyId;
    }

    /**
     * Get the treatment for completed tokens
     *
     * @return string One of the TOKEN_ constants.
     */
    public function getTokenCompleted()
    {
        return $this->_tokenCompleted;
    }

    /**
     * Get the id of the track to import to or null
     *
     * @return int $trackId
     */
    public function getTrackId()
    {
        return $this->_trackId;
    }

    /**
     * Get the treatment when no token exists
     *
     * @param string $noToken One of the TOKEN_ constants, but not TOKEN_OVERWRITE.
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setNoToken($noToken)
    {
        $this->_noToken = $noToken;
        return $this;
    }

    /**
     * Get the treatment when no token exists
     *
     * @param boolean $skip
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setSkipUnknownPatients($skip = false)
    {
        $this->_skipUnknownPatients = $skip;
        return $this;
    }

    /**
     * Set the id of the survey to import to
     *
     * @param int $surveyId
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setSurveyId($surveyId)
    {
        $this->_surveyId = $surveyId;
        return $this;
    }

    /**
     * Set the treatment for answered or double tokens
     *
     * @param string $tokenCompleted One f the TOKEN_ constants.
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setTokenCompleted($tokenCompleted)
    {
        $this->_tokenCompleted = $tokenCompleted;
        return $this;
    }

    /**
     * Set the id of the track to import to or null
     *
     * @param int $trackId
     * @return \Gems_Model_Translator_AnswerTranslatorAbstract (continuation pattern)
     */
    public function setTrackId($trackId)
    {
        $this->_trackId = $trackId;
        return $this;
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }

        // Get the real organization from the provider_id or code if it exists
        if (isset($row[$this->orgIdField], $this->orgTranslations[$row[$this->orgIdField]])) {
            $row[$this->orgIdField] = $this->orgTranslations[$row[$this->orgIdField]];
        }

        if ($this->_skipUnknownPatients && isset($row[$this->patientNrField], $row[$this->orgIdField])) {
            if (! $this->checkPatient($row[$this->patientNrField], $row[$this->orgIdField])) {
                return false;
            }
        }

        $row['track_id']  = $this->getTrackId();
        $row['survey_id'] = $this->_surveyId;
        $row['token']     = strtolower($this->findTokenFor($row));

        return $row;
    }

    /**
     * Validate the data against the target form
     *
     * @param array $row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function validateRowValues(array $row, $key)
    {
        $row = parent::validateRowValues($row, $key);

        $token = $this->loader->getTracker()->getToken($row['token'] ? $row['token'] : 'emptytoken');

        if ($token->exists) {
            // If token is not completed we can use it, otherwise it depends on the settings
            if ($token->isCompleted()) {
                switch ($this->getTokenCompleted()) {
                    case self::TOKEN_SKIP:
                        return false;

                    case self::TOKEN_ERROR:
                        $this->_addErrors(sprintf(
                                $this->_('Token %s is completed.'),
                                $token->getTokenId()
                                ), $key);
                        break;

                    // Intentional fall-through,
                    // other case are handled in SaveAnswerTask
                }
            }
        } else {
            switch ($this->getNoToken()) {
                case self::TOKEN_SKIP:
                    return false;

                case self::TOKEN_ERROR:
                    $this->_addErrors($this->getNoTokenError($row, $key), $key);
                    break;

                default:
                    $respTrack = $this->findRespondentTrackFor($row);

                    if ($respTrack) {
                        $row['resp_track_id'] == $respTrack;
                    } else {
                        $this->_addErrors(sprintf(
                                $this->_('No track for inserting found for %s.'),
                                implode(" / ", $row)
                                ), $key);
                    }
            }
        }

        return $row;
    }
}
