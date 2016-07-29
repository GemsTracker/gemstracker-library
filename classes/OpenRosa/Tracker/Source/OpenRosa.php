<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OpenRosa.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OpenRosa_Tracker_Source_OpenRosa extends \Gems_Tracker_Source_SourceAbstract
{
    /**
     * This holds the path to the location where the form definitions will be stored.
     *
     * @var string
     */
    protected $formDir;

    /**
     * This holds the path to the location where OpenRosa will store it's files.
     * Will be set on init to: GEMS_ROOT_DIR . '/var/uploads/openrosa/';
     *
     * @var string
     */
    protected $baseDir;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * Standard constructor for sources
     *
     * @param array $sourceData The information from gems__sources for this source.
     * @param \Zend_Db_Adapter_Abstract $gemsDb Do not want to copy db using registry because that is public and this should be private
     */
    public function __construct(array $sourceData, \Zend_Db_Adapter_Abstract $gemsDb)
    {
        parent::__construct($sourceData, $gemsDb);
        $this->baseDir = GEMS_ROOT_DIR . '/var/uploads/openrosa/';
        $this->formDir = $this->baseDir . 'forms/';
    }

    /**
     * Open the dir, suppressing possible errors and try to
     * create when it does not exist
     *
     * @param type $directory
     * @return Directory
     */
    protected function _checkDir($directory)
    {
        $eDir = @dir($directory);
        if (false == $eDir) {
            // Dir does probably not exist
            if (!is_dir($directory)) {
                if (false === @mkdir($directory, 0777, true)) {
                    \MUtil_Echo::pre(sprintf($this->translate->_('Directory %s not found and unable to create'), $directory), 'OpenRosa ERROR');
                } else {
                    $eDir = @dir($directory);
                }
            }
        }

        return $eDir;
    }

    /**
     * Returns the source surveyId for a given Gems survey Id
     *
     * @param type $surveyId
     * @return type
     */
    private function _getSid($surveyId)
    {
        return $this->tracker->getSurvey($surveyId)->getSourceSurveyId();
    }

    /**
     * Returns all surveys for synchronization
     *
     * @return array of sourceId values or false
     */
    protected function _getSourceSurveysForSynchronisation()
    {
        // First scan for new definitions
        $this->_scanForms();

        // Surveys in OpenRosa
        $db = $this->getSourceDatabase();

        $select = $db->select();
        $select->from('gems__openrosaforms', array('gof_id'));

        return $db->fetchCol($select);
    }


    protected function _scanForms()
    {
        $messages = array();
        $formCnt  = 0;
        $addCnt   = 0;
        $eDir = $this->_checkDir($this->formDir);

        if ($eDir !== false) {
            $model = $this->loader->getModels()->getOpenRosaFormModel();
            while (false !== ($filename = $eDir->read())) {
                if (substr($filename, -4) == '.xml') {
                    $formCnt++;
                    $form                       = new \OpenRosa_Tracker_Source_OpenRosa_Form($this->formDir . $filename);
                    $filter['gof_form_id']      = $form->getFormID();
                    $filter['gof_form_version'] = $form->getFormVersion();
                    $forms                      = $model->load($filter);

                    if (!$forms) {
                        $newValues = array();
                        $newValues['gof_id']           = null;
                        $newValues['gof_form_id']      = $form->getFormID();
                        $newValues['gof_form_version'] = $form->getFormVersion();
                        $newValues['gof_form_title']   = $form->getTitle();
                        $newValues['gof_form_xml']     = $filename;
                        $newValues                     = $model->save($newValues);
                        if (\Gems_Tracker::$verbose) {
                            \MUtil_Echo::r($newValues, 'added form');
                        }
                        $addCnt++;
                    }
                }
            }
        }

        $cache = GemsEscort::getInstance()->cache;
        $cache->clean();

        $messages[] = sprintf('Checked %s forms and added %s forms', $formCnt, $addCnt);
    }

    /**
     * Checks wether this particular source is active or not and should handle updating the gems-db
     * with the right information about this source
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return boolean
     */
    public function checkSourceActive($userId)
    {
        $active = true;

        $values['gso_active'] = $active ? 1 : 0;
        $values['gso_status'] = $active ? 'Active' : 'Inactive';
        $values['gso_last_synch'] = new \Zend_Db_Expr('CURRENT_TIMESTAMP');

        $this->_updateSource($values, $userId);

        return $active;
    }

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
    public function copyTokenToSource(\Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId = null)
    {
        // Maybe insert meta data  here?
    }

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
    public function getAnswerDateTime($fieldName, \Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $answers = $token->getRawAnswers();

        if (isset($answers[$fieldName]) && $answers[$fieldName]) {
            if (\Zend_Date::isDate($answers[$fieldName], \Zend_Date::ISO_8601)) {
                return new \MUtil_Date($answers[$fieldName], \Zend_Date::ISO_8601);
            }
            if (\Gems_Tracker::$verbose)  {
                \MUtil_Echo::r($answers[$fieldName], 'Missed answer date value:');
            }
        }
    }

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
    public function getCompletionTime(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        if ($name = $model->getMeta('start')) {
            return $this->getAnswerDateTime($name, $token, $surveyId);
        }

        return null;
    }

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
    public function getDatesList($language, $surveyId, $sourceSurveyId = null)
    {
        $result = array();

        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        $dateItems = $model->getItemsFor('type', Mutil_model::TYPE_DATETIME) + $model->getItemsFor('type', Mutil_model::TYPE_DATE);

        foreach ($dateItems as $name)
        {
            if ($label = $model->get($name, 'label')) {
                $result[$name] = $label;
            }
        }

        return $result;

    }

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
    public function getQuestionInformation($language, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();
        $result = array();

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $result[$name]['question'] = $label;
                if ($answers = $model->get($name, 'multiOptions')) {
                    $result[$name]['answers'] = $answers;
                }
            }
        }

        return $result;
    }

    /**
     * Returns an array containing fieldname => label for dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getQuestionList($language, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();
        $result = array();

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $result[$name] = $label;
            }
        }

        if ($model->getMeta('nested', false)) {
            // We have a nested model, add the nested questions
            $nestedModel = $model->get($model->getMeta('nestedName'), 'model');
            foreach($nestedModel->getItemsOrdered() as $name) {
                if ($label = $nestedModel->get($name, 'label')) {
                    $result[$name] = $label;
                }
            }
        }

        return $result;
    }

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
    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        $result = $model->loadFirst(array('token' => $tokenId));
        return $result;
    }

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
    public function getRawTokenAnswerRows(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        $data = $select->query()->fetchAll();
        if (is_array($data)) {
            $data = $this->getSurvey($surveyId, $sourceSurveyId)->getModel()->processAfterLoad($data);

            // Check for nested answers
            $model  = $this->getSurvey($surveyId, $sourceSurveyId)->getModel();
            $nested = $model->getMeta('nested', false);

            if ($nested) {
                $nestedName  = $model->getMeta('nestedName');
                $oldData     = $data;
                $data        = array();
                $nestedModel = $model->get($nestedName, 'model');
                $nestedKeys  = array();
                foreach ($nestedModel->getItemsOrdered() as $name)
                {
                    if ($label = $nestedModel->get($name, 'label')) {
                        $nestedKeys[$name]= $name;
                    }
                }
                foreach ($oldData as $idx => $row)
                {
                    if (array_key_exists($nestedName, $row)) {
                        $nestedRows = $row[$nestedName];
                        unset($row[$nestedName]);
                        foreach ($nestedRows as $idx2 => $nestedRow) {
                            $data[$idx . '_' . $idx2] = $row + array_intersect_key($nestedRow, $nestedKeys);
                        }
                    } else {
                        $data[$idx] = $row;
                    }
                }
            }
        }

        if ($data) {
            return $data;
        }
        return array();
    }

    /**
     * Returns the recordcount for a given filter
     *
     * @param array $filter filter array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int
     */
    public function getRawTokenAnswerRowsCount(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        $p = new \Zend_Paginator_Adapter_DbSelect($select);
        $count = $p->getCountSelect()->query()->fetchColumn();

        return $count;
    }

    /**
     * Get the select object to use for RawTokenAnswerRows
     *
     * @param array $filter
     * @param type $surveyId
     * @param type $sourceSurveyId
     * @return \Zend_Db_Select
     */
    public function getRawTokenAnswerRowsSelect(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        $select = $model->getSelect();
        $this->filterLimitOffset($filter, $select);

        return $select;
    }

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
    public function getStartTime(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        if ($name = $model->getMeta('start')) {
            return $this->getAnswerDateTime($name, $token, $surveyId);
        }

        return null;
    }

    /**
     *
     * @param type $surveyId
     * @param type $sourceSurveyId
     * @return \OpenRosa_Tracker_Source_OpenRosa_Form
     */
    public function getSurvey($surveyId, $sourceSurveyId = null)
    {
        if (is_null($sourceSurveyId)) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $surveyInfo = $this->getSurveyInfo($sourceSurveyId);
        $survey     = new \OpenRosa_Tracker_Source_OpenRosa_Form($this->formDir . $surveyInfo['gof_form_xml']);

        return $survey;
    }

    /**
     * Return info about the survey (row from gems__openrosaforms)
     *
     * @param int $sourceSurveyId
     * @return array
     */
    public function getSurveyInfo($sourceSurveyId)
    {
        $db = $this->getSourceDatabase();

        $select = $db->select();
        $select->from('gems__openrosaforms')
            ->where('gof_id = ?', $sourceSurveyId);

        return $db->fetchRow($select);
    }

    /**
     * Returns a model for the survey answers
     *
     * @param \Gems_Tracker_Survey $survey
     * @param string $language Optional (ISO) language string
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Model_ModelAbstract
     */
    public function getSurveyAnswerModel(\Gems_Tracker_Survey $survey, $language = null, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($survey->getSurveyId());
        }

        $surveyModel   = $this->getSurvey($survey->getSurveyId(), $sourceSurveyId)->getModel();
        $model         = new \OpenRosa_Tracker_Source_OpenRosa_Model($survey, $this);
        $questionsList = $this->getQuestionList($language, $survey->getSurveyId(), $sourceSurveyId);
        foreach($questionsList as $item => $question) {
            $allOptions = $surveyModel->get($item);
            $allowed = array_fill_keys(array('storageFormat', 'dateFormat', 'label', 'multiOptions', 'maxlength', 'type', 'itemDisplay', 'formatFunction'),1);
            $options = array_intersect_key($allOptions, $allowed);

            $options['label']           = strip_tags($question);
            $options['survey_question'] = true;

            //Should also do something to get the better titles...
            $model->set($item, $options);
        }

        return $model;
    }

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId)
    {
        // There is no url, so return null
        return;
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        // The token is always in source
        return true;
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $result = $this->getRawTokenAnswerRow($token->getTokenId(), $surveyId);
        $completed = !empty($result);

        return $completed;
    }

    /**
     * Sets the answers passed on.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param $answers array Field => Value array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setRawTokenAnswers(\Gems_Tracker_Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {

    }

    /**
     * Survey source synchronization check function
     *
     * @param string $sourceSurveyId
     * @param int $surveyId
     * @param int $userId
     * @return mixed message string or array of messages
     */
    public function checkSurvey($sourceSurveyId, $surveyId, $userId)
    {
        $changed  = 0;
        $created  = false;
        $deleted  = false;
        $survey   = $this->tracker->getSurvey($surveyId);

        // Get OpenRosa data
        if ($sourceSurveyId) {
            // Surveys in OpenRose
            $db = $this->getSourceDatabase();

            $select = $db->select();
            $select->from('gems__openrosaforms')
                    ->where('gof_id = ?', $sourceSurveyId);

            $openRosaSurvey = $db->fetchRow($select);
        } else {
            $openRosaSurvey = false;
        }

        if ($openRosaSurvey) {
            // Exists
            $values['gsu_survey_name']     = sprintf(
                    '%s [%s]',
                    $openRosaSurvey['gof_form_title'],
                    $openRosaSurvey['gof_form_version']
                    );
            $values['gsu_status']          = 'OK';
            $values['gsu_surveyor_active'] = $openRosaSurvey['gof_form_active'];

            if (!$surveyId) {
                // New
                $values['gsu_active']      = 0;
                $values['gsu_id_source']   = $this->getId();
                $values['gsu_surveyor_id'] = $sourceSurveyId;
                $created = true;
            }
        } else {
            // No longer exists
            $values['gsu_surveyor_active'] = 0;
            $values['gsu_status']          = 'Survey was removed from source.';

            $deleted = true;
        }

        $changed = $survey->saveSurvey($values, $userId);

        if (! $changed) {
            return;
        }

        if ($deleted) {
            $message = $this->_('The \'%s\' survey is no longer active. The survey was removed from OpenRosa!');

        } elseif ($created) {
            $message = $this->_('Imported the \'%s\' survey.');

        } else {
            $message = $this->_('Updated the \'%s\' survey.');
        }

        return sprintf($message, $survey->getName());;
    }

     /**
     * Sets the completion time.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param \Zend_Date|null $completionTime \Zend_Date or null
     * @param int $surveyId Gems Survey Id (actually required)
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setTokenCompletionTime(\Gems_Tracker_Token $token, $completionTime, $surveyId, $sourceSurveyId = null)
    {
        // Nothing to do, time is kept in Gems
    }

   /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     * /
    public function synchronizeSurveys($userId)
    {
        $messages = $this->_scanForms();

        // Surveys in LS
        $db = $this->getSourceDatabase();

        $select = $db->select();
        $select->from('gems__openrosaforms');

        $openRosaSurveys = $db->fetchAssoc($select);

        if (!$openRosaSurveys) {
            //If no surveys present, just use an empty array as array_combine fails
            $openRosaSurveys = array();
            $openRosaSurveyIds = array();
        } else {
            $openRosaSurveyIds = array_combine(array_keys($openRosaSurveys), array_keys($openRosaSurveys));
        }

        // Surveys in Gems
        $gemsSurveys = $this->_getGemsSurveysForSynchronisation();

        foreach ($gemsSurveys as $surveyId => $sourceSurveyId) {
            $survey = $this->tracker->getSurveyBySourceId($sourceSurveyId, $this->getId());
            if (isset($openRosaSurveyIds[$sourceSurveyId])) {
                // Exists
                $values['gsu_survey_name']        = $openRosaSurveys[$sourceSurveyId]['gof_form_title'] . ' [' . $openRosaSurveys[$sourceSurveyId]['gof_form_version'] .  ']';
                $values['gsu_surveyor_active']    = $openRosaSurveys[$sourceSurveyId]['gof_form_active'];
                $values['gsu_status']             = 'OK';
            } else {
                // No longer exists
                $values['gsu_surveyor_active'] = 0;
                $values['gsu_status']          = 'No longer exists';
            }

            $survey->saveSurvey($values, $userId);
        }

        foreach (array_diff($openRosaSurveyIds, $gemsSurveys) as $sourceSurveyId) {
            // New survey
            $values = array();
            $values['gsu_survey_name']        = $openRosaSurveys[$sourceSurveyId]['gof_form_title'] . ' [' . $openRosaSurveys[$sourceSurveyId]['gof_form_version'] .  ']';
            $values['gsu_surveyor_active']    = $openRosaSurveys[$sourceSurveyId]['gof_form_active'];
            $values['gsu_active']             = 0;
            $values['gsu_status']             = '';

            $survey         = $this->tracker->getSurveyBySourceId($sourceSurveyId, $this->getId());
            $survey->exists = false;
            $survey->saveSurvey($values, $userId);
        }

        return $messages;
    }

     /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param \Gems_Tracker_Token $token
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
   public function updateConsent(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null)
    {

    }
}