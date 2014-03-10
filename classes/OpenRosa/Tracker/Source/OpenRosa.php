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
 * Short description of file
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Short description for OpenRosaSource
 *
 * Long description for class OpenRosaSource (if any)...
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 * @deprecated Class deprecated since version 2.0
 */
class OpenRosa_Tracker_Source_OpenRosa extends Gems_Tracker_Source_SourceAbstract
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
     * @var Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @var Zend_Translate
     */
    protected $translate;
    

    public function __construct(array $sourceData, Zend_Db_Adapter_Abstract $gemsDb)
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
                    MUtil_Echo::pre(sprintf($this->translate->_('Directory %s not found and unable to create'), $directory), 'OpenRosa ERROR');
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
                    $form                       = new OpenRosa_Tracker_Source_OpenRosa_Form($this->formDir . $filename);
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
                        if (Gems_Tracker::$verbose) {
                            MUtil_Echo::r($newValues, 'added form');
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

    //put your code here
    public function checkSourceActive($userId)
    {
        $active = true;

        $values['gso_active'] = $active ? 1 : 0;
        $values['gso_status'] = $active ? 'Active' : 'Inactive';
        $values['gso_last_synch'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');

        $this->_updateSource($values, $userId);

        return $active;
    }

    public function copyTokenToSource(Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId = null)
    {
        // Maybe insert meta data  here?      
    }

    public function getAnswerDateTime($fieldName, Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        
    }
    
    public function getCompletionTime(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        
    }

    public function getDatesList($language, $surveyId, $sourceSurveyId = null)
    {
        
    }

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

        return $result;
    }

    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        $result = $model->loadFirst(array('token' => $tokenId));
        return $result;
    }

    public function getRawTokenAnswerRows(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);
        
        $data = $select->query()->fetchAll();
        if (is_array($data)) {
            $data = $this->getSurvey($surveyId, $sourceSurveyId)->getModel()->processAfterLoad($data);
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
        
        $p = new Zend_Paginator_Adapter_DbSelect($select);
        $count = $p->getCountSelect()->query()->fetchColumn();
        
        return $count;
    }
    
    /**
     * Get the select object to use for RawTokenAnswerRows
     * 
     * @param array $filter
     * @param type $surveyId
     * @param type $sourceSurveyId
     * @return Zend_Db_Select
     */
    public function getRawTokenAnswerRowsSelect(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $survey = $this->getSurvey($surveyId, $sourceSurveyId);
        $model  = $survey->getModel();

        $select = $model->getSelect();
        $this->filterLimitOffset($filter, $select);
        
        return $select;
    }

    public function getStartTime(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {

    }

    public function getSurvey($surveyId, $sourceSurveyId = null)
    {
        if (is_null($sourceSurveyId)) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $surveyInfo = $this->getSurveyInfo($sourceSurveyId);
        $survey     = new OpenRosa_Tracker_Source_OpenRosa_Form($this->formDir . $surveyInfo['gof_form_xml']);      

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

    public function getSurveyAnswerModel(Gems_Tracker_Survey $survey, $language = null, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($survey->getSurveyId());
        }
        
        $surveyModel   = $this->getSurvey($survey->getSurveyId(), $sourceSurveyId)->getModel();
        $model         = new OpenRosa_Tracker_Source_OpenRosa_Model($survey, $this);
        $questionsList = $this->getQuestionList($language, $survey->getSurveyId(), $sourceSurveyId);
        foreach($questionsList as $item => $question) {
            $allOptions = $surveyModel->get($item);
            $allowed = array_fill_keys(array('storageFormat', 'dateFormat', 'label', 'multiOptions', 'maxlength', 'type', 'itemDisplay', 'formatFunction'),1);
            $options = array_intersect_key($allOptions, $allowed);

            $options['label'] = strip_tags($question);

            //Should also do something to get the better titles...
            $model->set($item, $options);
        }
       
        return $model;
    }

    public function getTokenUrl(Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId)
    {
        // There is no url, so return null
        return;
    }

    public function inSource(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        // The token is always in source
        return true;
    }

    public function isCompleted(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $result = $this->getRawTokenAnswerRow($token->getTokenId(), $surveyId);
        $completed = !empty($result);
        
        return $completed;
    }

    public function setRawTokenAnswers(Gems_Tracker_Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {
        
    }

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
                $values['gsu_status']             = 'Ok';
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

    public function updateConsent(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null)
    {

    }
}