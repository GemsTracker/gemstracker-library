<?php

/**
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace OpenRosa\Tracker\Source;

use DateTimeImmutable;
use DateTimeInterface;
use MUtil\Model;

use OpenRosa\Tracker\Source\Form;
use OpenRosa\Tracker\Model\SurveyModel;

/**
 *
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OpenRosa extends \Gems\Tracker\Source\SourceAbstract
{
    protected $_attributeMap = array(
        'gto_id_respondent',
        'gto_id_organization',
        'gto_id_respondent_track',
        'gto_round_description',
    );

    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @var \Zend_Db
     */
    protected $db;

    /**
     * This holds the path to the location where OpenRosa will store it's files.
     * Will be set on init to: GEMS_ROOT_DIR . '/var/uploads/openrosa/';
     *
     * @var string
     */
    protected $baseDir;

    /**
     * This holds the path to the location where the form definitions will be stored.
     *
     * @var string
     */
    protected $formDir;

    /**
     *
     * @var \Gems\Loader
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
                    \MUtil\EchoOut\EchoOut::pre(sprintf($this->translate->_('Directory %s not found and unable to create'), $directory), 'OpenRosa ERROR');
                } else {
                    $eDir = @dir($directory);
                }
            }
        }

        return $eDir;
    }

    /**
     * Returns the source surveyId for a given \Gems survey Id
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
        $select->from('gems__openrosaforms', ['gof_id']);

        return $db->fetchCol($select);
    }


    protected function _scanForms()
    {
        $messages = [];
        $formCnt  = 0;
        $addCnt   = 0;
        $eDir = $this->_checkDir($this->formDir);

        if ($eDir !== false) {
            $model = $this->loader->getModels()->getOpenRosaFormModel();
            while (false !== ($filename = $eDir->read())) {
                if (substr($filename, -4) == '.xml') {
                    $formCnt++;
                    $form                       = $this->createForm($this->formDir . $filename);
                    $filter['gof_form_id']      = $form->getFormID();
                    $filter['gof_form_version'] = $form->getFormVersion();
                    $forms                      = $model->load($filter);

                    if (!$forms) {
                        $newValues = [];
                        $newValues['gof_id']           = null;
                        $newValues['gof_form_id']      = $form->getFormID();
                        $newValues['gof_form_version'] = $form->getFormVersion();
                        $newValues['gof_form_title']   = $form->getTitle();
                        $newValues['gof_form_xml']     = $filename;
                        $newValues                     = $model->save($newValues);
                        if (\Gems\Tracker::$verbose) {
                            \MUtil\EchoOut\EchoOut::r($newValues, 'added form');
                        }
                        $addCnt++;
                    }
                }
            }
        }

        $this->cache->clean();

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

        $this->_updateSource($values, $userId);

        return $active;
    }

    /**
     * Create Open Rosa Model
     *
     * @param \Gems\Tracker\Survey $survey
     * @return Model
     */
    protected function createModel(\Gems\Tracker\Survey $survey)
    {
        return new SurveyModel($survey, $this, $this->tracker);
    }

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param \Gems\Tracker\Token $token
     * @param string $language
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems\Tracker\Source\SurveyNotFoundException
     */
    public function copyTokenToSource(\Gems\Tracker\Token $token, $language, $surveyId, $sourceSurveyId = null)
    {
        // Maybe insert meta data  here?
    }

    /**
     * returns an Open Rosa Form
     *
     * @param $filename absolute filename
     * @return \OpenRosa\Tracker\Source\OpenRosa\Form
     * @throws \Gems\Exception\Coding
     */
    protected function createForm($filename)
    {
        return new Form($filename, $this->db, $this->translate);
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime($fieldName, \Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        $answers = $token->getRawAnswers();

        if (isset($answers[$fieldName]) && $answers[$fieldName]) {
            $date = Model::getDateTimeInterface($answers[$fieldName]);
            if ($date) {
                return $date;
            }
            if (\Gems\Tracker::$verbose)  {
                \MUtil\EchoOut\EchoOut::r($answers[$fieldName], 'Missed answer date value:');
            }
        }
    }

    public function getAttributes()
    {
        return $this->_attributeMap;
    }

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems\Tracker\Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getCompletionTime(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
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
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getDatesList($language, $surveyId, $sourceSurveyId = null)
    {
        $result = [];

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
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Nested array
     * @deprecated since version 1.8.4 remove in 1.8.5
     */
    public function getFullQuestionList($language, $surveyId, $sourceSurveyId = null)
    {
        return $this->getQuestionList($language, $surveyId, $sourceSurveyId);
    }
    
    protected function getQuestionInfo($model) {
        $result = [];
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $answers = [];
                if ($model->has($name, 'multiOptions')) {
                    $answers = $model->get($name, 'multiOptions');
                }
                if (empty($answers)) {
                    $answers = $this->getType($model->get($name, 'type'));
                }
                $result[$name] = [
                    'question' => $label,
                    'type'     => $this->getType($model->get($name, 'type')),
                    'answers' => $answers
                ];                
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
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Nested array
     */
    public function getQuestionInformation($language, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();
        $result = $this->getQuestionInfo($model);

        if ($model->getMeta('nested', false)) {
            // We have a nested model, add the nested questions
            $nestedNames = $model->getMeta('nestedNames');
            foreach($nestedNames as $nestedName) {
                if ($nestedName instanceof \Gems\Model\JoinModel) {
                    $nestedModel = $nestedName;
                } else {
                    $nestedModel = $model->get($nestedName, 'model');
                }
                
                $result = $result + $this->getQuestionInfo($nestedModel);
            }
        }

        return $result;
    }    

    /**
     * Returns an array containing fieldname => label for dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getQuestionList($language, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();
        $result = [];

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $result[$name] = $label;
            }
        }

        if ($model->getMeta('nested', false)) {
            foreach ($model->getMeta('nestedNames') as $name) {
                // We have a nested model, add the nested questions
                $nestedModel = $model->get($name, 'model');
                foreach($nestedModel->getItemsOrdered() as $name) {
                    if ($label = $nestedModel->get($name, 'label')) {
                        $result[$name] = $label;
                    }
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
     * @param string $tokenId \Gems Token Id
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        $result = $model->loadFirst(['token' => $tokenId]);
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
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();
        $answers = $model->load($filter);

        $data = [];
        foreach($answers as $key=>$answer) {
            $data[$answer['token']] = $answer;
        }

        if ($data) {
            return $data;
        }
        return [];
    }

    /**
     * Returns the recordcount for a given filter
     *
     * @param array $filter filter array
     * @param int $surveyId \Gems Survey Id
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
     * it well enough). In the case \Gems\Tracker\Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getStartTime(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
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
        $survey     = $this->createForm($this->formDir . $surveyInfo['gof_form_xml']);

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
     * @param \Gems\Tracker\Survey $survey
     * @param string $language Optional (ISO) language string
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil\Model\ModelAbstract
     */
    public function getSurveyAnswerModel(\Gems\Tracker\Survey $survey, $language = null, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($survey->getSurveyId());
        }

        $model         = $this->createModel($survey);
        $model->set('gto_id_token', 'label', $this->_('Token'), 'elementClass', 'Exhibitor');

        $surveyForm  = $this->getSurvey($survey->getSurveyId(), $sourceSurveyId);
        $surveyModel   = $this->getSurvey($survey->getSurveyId(), $sourceSurveyId)->getModel();

        $model->setMeta('openroseTableName', $surveyForm->getTableName());
        $model->setMeta('internalAttributes', true);

        /*$questionsList = $this->getQuestionList($language, $survey->getSurveyId(), $sourceSurveyId);
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
        */

        foreach($surveyModel->getItemsOrdered() as $name) {
            $label = $surveyModel->get($name, 'label');
            if ($label) {
                $options = $surveyModel->get($name);
                unset($options['table']);

                $options['label']           = strip_tags($label);
                $options['label_raw']       = $label;
                $options['survey_question'] = true;

                //Should also do something to get the better titles...
                $model->set($name, $options);
            }
        }

        if ($tableIdField = $model->getTableIdField()) {
            if ($surveyModel->has($tableIdField) && !$surveyModel->get($tableIdField, 'label')) {
                $options = $surveyModel->get($name);
                unset($options['table']);
                $model->set($tableIdField, $options);
            }
        }

        if ($surveyModel->getMeta('nested', false)) {
            $model->setMeta('nested', true);
            // We have a nested model, add the nested questions
            $nestedNames = $surveyModel->getMeta('nestedNames');
            $model->setMeta('nestedNames', $nestedNames);
            foreach($nestedNames as $nestedName) {

                $nestedModel = $surveyModel->get($nestedName, 'model');
                if ($nestedModel instanceof \MUtil\Model\ModelAbstract) {
                    $model->addListModel($nestedModel, array('orf_id' => 'orfr_response_id'));
                    $model->set($nestedName, 'label', $surveyModel->get($nestedName, 'label'),
                        'elementClass', 'FormTable'
                    );
                    $model->setMeta('nestedNames', $nestedNames);
                }
            }
        }
        $model->del('token');

        return $model;
    }

    /**
     * Returns a \Gems Tracker token from a token ID
     *
     * @param string $tokenId \Gems Token Id
     * @return \Gems\Tracker\Token
     * @deprecated
     */
    public function getToken($tokenId)
    {
        $tracker = $this->loader->getTracker();
        $token = $tracker->getToken($tokenId);
        return $token;
    }

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param string $language
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems\Tracker\Token $token, $language, $surveyId, $sourceSurveyId)
    {
        // There is no url, so return null
        $basePath = \Gems\Escort::getInstance()->basePath->__toString();
        return $basePath . '/open-rosa-form/edit/id/' . $token->getTokenId();
    }
    
    public function getType($type)
    {
        static $typeList = null;
        static $default  = null;
        
        if(is_null($typeList)) {
            $typeList = [
                \MUtil\Model::TYPE_DATETIME => $this->_('Date and time'),
                \MUtil\Model::TYPE_DATE => $this->_('Date'),
                \MUtil\Model::TYPE_TIME => $this->_('Time'),
                \MUtil\Model::TYPE_NUMERIC => $this->_('Free number'),
                \MUtil\Model::TYPE_STRING => $this->_('Free text'),
                \MUtil\Model::TYPE_NOVALUE => $this->_('None')
                    ];
                
            $default = $this->_('Unknown');
        }
        
        if (array_key_exists($type, $typeList)) {
            return $typeList[$type];
        }
        
        return $default;
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        // The token is always in source
        return true;
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        $result = $this->getRawTokenAnswerRow($token->getTokenId(), $surveyId);
        $completed = !empty($result);

        return $completed;
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
        $changed     = 0;
        $created     = false;
        $deleted     = false;
        $deletedFile = false;
        $survey      = $this->tracker->getSurvey($surveyId);

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

            if (!$this->sourceFileExists($openRosaSurvey['gof_form_xml'])) {
                // No longer exists
                $values['gsu_surveyor_active'] = 0;
                $values['gsu_status']          = 'Survey form XML was removed from directory.';
                $deletedFile = true;
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

        } elseif ($deletedFile) {
            $message = $this->_('The \'%s\' survey is no longer active. The survey XML file was removed from its directory!');

        } elseif ($created) {
            $message = $this->_('Imported the \'%s\' survey.');

        } else {
            $message = $this->_('Updated the \'%s\' survey.');
        }

        return sprintf($message, $survey->getName());;
    }

    /**
     * Sets the answers passed on.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param $answers array Field => Value array
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setRawTokenAnswers(\Gems\Tracker\Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        // new \Zend_Db_Table(array('db' => $this->getSourceDatabase(), 'name' => 'odk__etc'))
        $answers['token'] = $answers['gto_id_token'];

        $model->save($answers);

        return $model->getChanged();
    }

     /**
     * Sets the completion time.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param \DateTimeInterface|null $completionTime \DateTimeInterface or null
     * @param int $surveyId \Gems Survey Id (actually required)
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setTokenCompletionTime(\Gems\Tracker\Token $token, $completionTime, $surveyId, $sourceSurveyId = null)
    {
        // Nothing to do, time is kept in \Gems
    }

    /**
     * Check if the xml file still exists in the directory
     *
     * @param $filename
     * @return bool
     */
    protected function sourceFileExists($filename)
    {
        $fullFilename = $this->formDir . $filename;
        if (file_exists($fullFilename)) {
            return true;
        }

        return false;
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

        // Surveys in \Gems
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
     * @param \Gems\Tracker\Token $token
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
   public function updateConsent(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null)
    {

    }
}