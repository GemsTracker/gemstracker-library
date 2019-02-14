<?php

namespace Gems\Snippets\Survey;

class SurveyCompareSnippet extends \MUtil_Snippets_WizardFormSnippetAbstract {

    /**
     *
     * @var \Zend_Session_Namespace
     */
    protected $_session;
    
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Zend_Locale
     */
    public $locale;

    /**
     *
     * @var \MUtil_Model
     */
    protected $model;

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     * @var int Id of the source survey
     */
    protected $sourceSurveyId;

    /**
     * @var array list of question statusses used in the question compare functions and their table row classes
     */
    protected $questionStatusClasses = [
        'same'            => 'success',
        'new'             => 'info',
        'type-difference' => 'warning',
        'missing'         => 'danger',
    ];

    /**
     * @var array List Survey ID => Survey name of available surveys. Initialized on load
     */
    protected $surveys;

    /**
     * @var int Id of the tartget Survey
     */
    protected $targetSurveyId;

    /**
     * @var \Gems_Util
     */
    public $util;
    
    /**
     *
     * @var \Gems_View
     */
    public $view;

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model, $step) {
        $this->displayHeader($bridge, $this->_('Survey replace'), 'h1');

        // If we don't copy answers, we skip step 2
        if ($this->formData['copy_answers'] == 0 && $step > 1) {
            $step = $step + 1;
        }

        // To prevent confusion we show a progressbar that jumps from 25% to 50% or 75%
        $element = $bridge->getForm()->createElement('html', 'progress');
        $bridge->addElement($element);
        $element->div(array('class' => 'progress', 'renderClosingTag' => true))->div('', array(
            'class'            => 'progress-bar',
            'style'            => 'width: ' . $step / $this->getStepCount() * 100 . '%;',
            'renderClosingTag' => true));

        switch ($step) {
            case 0:
            case 1:
                $this->addStepElementsForStep1($bridge, $model);
                break;

            case 2:
                $this->addStepElementsForStep2($bridge, $model);
                break;

            case 3:
                $this->addStepElementsForStep3($bridge, $model);
                break;

            default:
                $this->addStepElementsForStep4($bridge, $model);
                break;
        }

        return;
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepElementsForStep1(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model) {
        $model->set('source_survey', 'elementClass', 'Select');
        $this->addItems($bridge, 'source_survey', 'target_survey');
        $this->addItems($bridge, 'track_replace', 'token_update', 'copy_answers');
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepElementsForStep2(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model) {
        $element = $bridge->getForm()->createElement('html', 'table');
        $bridge->addElement($element);

        $table = $element->table(['class' => 'browser table']);
        $table->caption($this->getTitle());

        $headers = [
            $this->_('Question code'),
            $this->_('Source Survey'),
            $this->_('Target Survey'),
        ];

        $tableHeader = $table->thead();

        $headerRow = $tableHeader->tr();
        foreach ($headers as $label) {
            $headerRow->th($label);
        }

        $headerRow = $tableHeader->tr();
        $headerRow->th();
        $headerRow->th($this->surveys[$this->sourceSurveyId]);
        $headerRow->th($this->surveys[$this->targetSurveyId]);

        $tableBody = $table->tbody();

        $row = $tableBody->tr();
        $row->td($this->_('Usage'));
        $row->td($this->getSurveyStatistics($this->sourceSurveyId));
        $row->td($this->getSurveyStatistics($this->targetSurveyId));

        $this->addSurveyCompareForm($tableBody, $this->formData);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepElementsForStep3(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model) {
        //$this->_form->append($this->getSurveyCompareTable());
        //$this->addItems($bridge, 'target_survey', 'source_survey');
        $element = $bridge->getForm()->createElement('html', 'table');
        $bridge->addElement($element);
        $element->append($this->getSurveyResults($this->formData));
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepElementsForStep4(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model) {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $batch = $this->getUpdateBatch();
        $form  = $bridge->getForm();
        
        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil_Registry_Source::$verbose = true;
        if ($batch->run($this->request)) {
            exit;
        }

        $element = $form->createElement('html', $batch->getId());

        if ($batch->isFinished()) {
            // Keep the filename after $batch->getMessages(true) cleared the previous
            $this->addMessage($batch->getMessages(true));
            $element->h3($this->_('Survey replaced successfully!'));
            $this->nextDisabled = false;
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);
    }

    /**
     * Adds the survey compare form to the current table, showing the matches between different surveys
     *
     * @param $tableBody \MUtil_Html Table object
     * @param $post array List of Post data
     */
    public function addSurveyCompareForm($tableBody, $post) {
        if ($this->sourceSurveyId && $this->targetSurveyId) {
            $sourceSurveyData = $this->getSurveyData($this->sourceSurveyId);
            $targetSurveyData = $this->getSurveyData($this->targetSurveyId);
            $surveyCompare    = $this->getSurveyCompare($sourceSurveyData, $targetSurveyData, $post);

            $icon = \MUtil_Html::create()->i(['class' => 'fa fa-exclamation-triangle', 'style' => 'color: #d43f3a; margin: 1em;', 'renderClosingTag' => true]);

            foreach ($surveyCompare as $question) {
                if ($question['status'] == 'missing') {
                    continue;
                }
                $rowMessage  = false;
                $statusClass = '';

                if (isset($this->questionStatusClasses[$question['status']])) {
                    $statusClass = $this->questionStatusClasses[$question['status']];
                }

                if ($question['status'] == 'type-difference') {
                    $rowMessage = $this->_('Question type is not the same. Check compatibility!');
                } elseif ($question['status'] == 'new') {
                    $rowMessage = $this->_('Question could not be found in source. Is this a new question?');
                } /*elseif ($question['status'] == 'missing') {
                    $rowMessage = $this->_('Warning! Question not found in target survey. Data will be lost on transfer');
                }*/

                $row = $tableBody->tr(['class' => $statusClass]);
                $row->td($question['target']);

                // Source column
                $row->td($this->getSurveyQuestionSelect($sourceSurveyData, $question['source'], $question['target']));

                // Target column
                if (isset($targetSurveyData[$question['target']])) {
                    $row->td($targetSurveyData[$question['target']]['question']);
                } else {
                    $row->td();
                }

                if ($rowMessage) {
                    $tableBody->tr(['class' => $statusClass])->td(['colspan' => 3])->append($icon, $rowMessage);
                }
            }
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry() {
        parent::afterRegistry();

        $this->getSurveys();
    }

    /**
     * Generates the lime_survey_xxx query from the selected survey links and the table structure
     *
     * @param $post array List of POST data
     * @param $sourceSurveyData array List of question information from the source survey
     * @param $targetSurveyData array List of question information from the target survey
     * @return string SQL query inserting question answers into the target survey
     */
    public function buildSurveyQuery($post, $sourceSurveyData, $targetSurveyData) {
        $sourceTableStructure = $this->getSurveyTableStructure($this->sourceSurveyId);
        $targetTableStructure = $this->getSurveyTableStructure($this->targetSurveyId);

        $targetSourceSurveyId = (string) $this->getSourceSurveyId($this->targetSurveyId);

        $targetTableColumns = [];
        $sourceTableColumns = [];
        if (isset($post['target'])) {
            foreach ($post['target'] as $targetColumn => $sourceColumn) {
                if (!empty($sourceColumn)) {
                    $targetTableColumns[] = $targetSurveyData[$targetColumn]['id'];
                    $sourceTableColumns[] = $sourceSurveyData[$sourceColumn]['id'];
                }
            }
        }
        $initColumns = [];
        foreach ($targetTableStructure as $columnName => $questionStructure) {
            if (strpos($columnName, $targetSourceSurveyId) !== 0) {
                $initColumns[$columnName] = $columnName;
            }
        }

        if (isset($initColumns['id'])) {
            unset($initColumns['id']);
        }

        $sourceFirstColumn = reset($sourceTableStructure);
        $sourceTable       = $sourceFirstColumn['TABLE_NAME'];

        $targetFirstColumn = reset($targetTableStructure);
        $targetTable       = $targetFirstColumn['TABLE_NAME'];

        $sql = "INSERT INTO {$targetTable} (" . join(', ', $initColumns) . ',' . join(', ', $targetTableColumns) . ")";
        $sql .= '(SELECT ' . join(', ', $initColumns) . ',' . join(', ', $sourceTableColumns) . " FROM " . $sourceTable . ')';

        return $sql;
    }

    /**
     * Generates a lime_tokens_xxx query from the table structure the source and target queries
     *
     * @return string SQL query inserting existing tokens into the target survey
     */
    public function buildTokenQuery() {
        $sourceTokenTableStructure = $this->getTokenTableStructure($this->sourceSurveyId);
        $targetTokenTableStructure = $this->getTokenTableStructure($this->targetSurveyId);

        $sourceFirstColumn = reset($sourceTokenTableStructure);
        $sourceTable       = $sourceFirstColumn['TABLE_NAME'];

        $targetFirstColumn = reset($targetTokenTableStructure);
        $targetTable       = $targetFirstColumn['TABLE_NAME'];

        $bothTokenTableStructures = [];
        foreach ($targetTokenTableStructure as $columnName => $columnData) {
            if (isset($sourceTokenTableStructure[$columnName])) {
                $bothTokenTableStructures[$columnName] = true;
            }
        }

        if (isset($bothTokenTableStructures['tid'])) {
            unset($bothTokenTableStructures['tid']);
        }

        $sql = "INSERT INTO {$targetTable} (" . join(', ', array_keys($bothTokenTableStructures)) . ")";
        $sql .= "\n";
        $sql .= '(SELECT ' . join(', ', array_keys($bothTokenTableStructures)) . ' FROM ' . $sourceTable . ')';

        return $sql;
    }

    /**
     * Gets how many times a survey is used in tracks
     *
     * @param $surveyId int Gems Survey ID
     * @return array|\MUtil_Html_Sequence translated string with track usage
     */
    public function calculateTrackUsage($surveyId) {
        $select = $this->db->select();
        $select->from('gems__tracks', array('gtr_track_name'));
        $select->joinLeft('gems__rounds', 'gro_id_track = gtr_id_track', array('useCnt' => 'COUNT(*)'))
                ->where('gro_id_survey = ?', $surveyId)
                ->group('gtr_track_name');
        $usage  = $this->db->fetchPairs($select);

        if ($usage) {
            $seq = new \MUtil_Html_Sequence();
            $seq->setGlue(\MUtil_Html::create('br'));
            foreach ($usage as $track => $count) {
                $seq[] = sprintf($this->plural(
                                'Used %d time in %s track.',
                                'Used %d times in %s track.',
                                $count), $count, $track);
            }
            return $seq;
        } else {
            return $this->_('Not used in any track.');
        }
    }

    protected function createModel() {
        if (!$this->model instanceof \MUtil_Model_ModelAbstract) {
            // $model = new \MUtil_Model_TableModel
            $model = new \MUtil_Model_SessionModel('updatesurvey');

            $surveys = $this->surveys;

            $empty         = $this->util->getTranslated()->getEmptyDropdownArray();
            $surveyOptions = $empty + $surveys;

            $model->set('source_survey', 'label', $this->_('Source Survey'), 'multiOptions', $surveyOptions, 'required', true);
            $validator = new \MUtil_Validate_NotEqualTo(['source_survey'], ['source_survey' => $this->_('Source and target survey can not be the same')]);
            $model->set('target_survey', 'label', $this->_('Target Survey'), 'multiOptions', $surveyOptions, 'required', true, 'validator', $validator);

            $model->set('track_replace', 'label', $this->_('Replace in track definitions'), 'description', $this->_('Replace all occurances of old survey in all tracks with the new survey'),
                    'elementClass', 'checkbox', 'default', 1);

            $model->set('token_update', 'label', $this->_('Replace in existing tracks'), 'description', $this->_('Update all existing gemstracker tokens to point to the new survey'),
                    'elementClass', 'checkbox', 'default', 0);

            // survey_query, token_query
            $model->set('copy_answers', 'label', $this->_('Copy survey answers'), 'description', $this->_('Copy all survey answers into the new survey'),
                    'elementClass', 'checkbox', 'default', 0);
            
            // Disable copying answers until problems are solved with different survey sources (ie. multisite ls)
            //$model->set('copy_answers', 'disabled', true);

            // Storage for local copy of the file, kept through process
            $model->set('import_id');

            $this->model = $model;
        }

        return $this->model;
    }

    /**
     * Display a header
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param mixed $header Header content
     * @param string $tagName
     */
    protected function displayHeader(\MUtil_Model_Bridge_FormBridgeInterface $bridge, $header, $tagName = 'h2') {
        $element = $bridge->getForm()->createElement('html', 'step_header');
        $element->$tagName($header);

        $bridge->addElement($element);
    }

    /**
     * Function to get Survey compare results sorted by status
     *
     * @param $post Post request data
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     * @return array compare results sorted by status
     */
    protected function getCategorizedResults($post, $sourceSurveyData, $targetSurveyData) {
        $surveyCompare = $this->getSurveyCompare($sourceSurveyData, $targetSurveyData, $post);

        $categorizedResults = [];
        foreach ($this->questionStatusClasses as $status => $class) {
            $categorizedResults[$status] = [];
        }

        foreach ($surveyCompare as $result) {
            if (isset($result['status'])) {                
                if ($result['status'] == 'missing') {
                    $categorizedResults[$result['status']][$result['source']] = $result;
                } else {
                    $categorizedResults[$result['status']][$result['target']] = $result;
                }
            } else {
                $categorizedResults['other'][] = $result;
            }
        }
        return $categorizedResults;
    }

    /**
     * Creates a table with warnings about the survey answer transfer
     *
     * @return bool|\MUtil_Html Table with information
     */
    public function getComments() {
        $comments = false;
        $table    = \MUtil_Html::create()->table(['class' => 'browser table', 'style' => 'width: auto']);

        $targetSurveyAnswers = $this->getNumberOfAnswers($this->targetSurveyId);
        if ($targetSurveyAnswers > 0) {
            $comments = true;
            $table
                    ->tr(['class' => 'warning'])
                    ->td(
                            sprintf(
                                    $this->_('Target survey already has %d answers. Is this expected?'),
                                    $targetSurveyAnswers
                            )
            );
        }

        if ($comments) {
            return $table;
        }

        return false;
    }

    /**
     * Creates a table with comparison summary
     *
     * @param $post array List of post values
     * @param $sourceSurveyData array list of survey structure
     * @param $targetSurveyData
     * @return mixed
     */
    protected function getCompareResultSummary($post, $sourceSurveyData, $targetSurveyData) {
        $categorizedResults = $this->getCategorizedResults($post, $sourceSurveyData, $targetSurveyData);
        $table              = \MUtil_Html::create()->table(['class' => 'browser table', 'style' => 'width: auto']);

        $row = $table->tr(['class' => $this->questionStatusClasses['new']]);
        $row->td(sprintf($this->_('%d new questions'), count($categorizedResults['new'])));
        $row->td(join(', ', array_keys($categorizedResults['new'])));

        $row = $table->tr(['class' => $this->questionStatusClasses['same']]);
        $row->td(sprintf($this->_('%d questions without warnings '), count($categorizedResults['same'])));
        $row->td(join(', ', array_keys($categorizedResults['same'])));

        $row = $table->tr(['class' => $this->questionStatusClasses['missing']]);
        $row->td(sprintf($this->_('%d missing questions'), count($categorizedResults['missing'])));
        $row->td(join(', ', array_keys($categorizedResults['missing'])));

        $row = $table->tr(['class' => $this->questionStatusClasses['type-difference']]);
        $row->td(sprintf($this->_('%d questions where the question type has changed'), count($categorizedResults['type-difference'])));
        $row->td(join(', ', array_keys($categorizedResults['type-difference'])));

        return $table;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view) {
        $form = parent::getHtmlOutput($view);

        $html = \MUtil_Html::create()->div(['id' => 'survey-compare']);

        $html->append($form);

        return $html;
    }

    /**
     * Gets the number of answers in a survey
     *
     * @param $surveyId int Gems Survey ID
     * @return string translated string with number of answers
     */
    public function getNumberOfAnswers($surveyId) {
        $fields['tokenCount'] = 'COUNT(DISTINCT gto_id_token)';
        $select               = $this->loader->getTracker()->getTokenSelect($fields)->andReceptionCodes([]);
        $select->forSurveyId($surveyId)
                ->onlySucces()
                ->onlyCompleted();
        $row                  = $select->fetchRow();
        return sprintf($this->_('Answered surveys: %d.'), $row['tokenCount']);
    }

    /**
     * Get the survey ID in the survey source
     *
     * @param $surveyId int Gems survey ID
     * @return int source survey ID
     */
    public function getSourceSurveyId($surveyId) {
        $tracker = $this->loader->getTracker();
        $survey  = $tracker->getSurvey($surveyId);

        return $survey->getSourceSurveyId();
    }

    protected function getStepCount() {
        if ($this->formData['copy_answers'] == 0) {
            return 3;
        }
        
        return 4;
    }

    /**
     * create an array with statusses of survey questions and how they're matched in the form
     *
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     * @param $post array List of POST data
     * @return array status array
     */
    public function getSurveyCompare($sourceSurveyData, $targetSurveyData, $post) {
        $surveyCompareArray        = [];
        $missingSourceSurveyTitles = $sourceSurveyData;

        // show all questions that can be send to the target survey
        foreach ($targetSurveyData as $questionCode => $questionData) {
            /* $currentSourceQuestionId = $questionData['id'];
              if (isset($post[$currentSourceQuestionId])) {
              $currentSourceQuestionId = $post[$currentQuestion];
              } */

            $currentQuestionCode = $questionCode;
            if (isset($post['target']) && isset($post['target'][$currentQuestionCode])) {
                $currentQuestionCode = $post['target'][$currentQuestionCode];
            }

            $questionCompare             = [
                'target' => $questionCode,
                'source' => $currentQuestionCode,
            ];
            $existingSourceQuestionTitle = null;

            if (isset($sourceSurveyData[$currentQuestionCode])) {
                if ($questionData['type'] === $sourceSurveyData[$currentQuestionCode]['type']) {
                    $questionCompare['status'] = 'same';
                } else {
                    $questionCompare['status'] = 'type-difference';
                }
                unset($missingSourceSurveyTitles[$currentQuestionCode]);
            } else {
                $questionCompare['status'] = 'new';
            }
            $surveyCompareArray[] = $questionCompare;
        }

        // show all questions that are missing in the target survey
        foreach ($missingSourceSurveyTitles as $questionId => $questionData) {
            $surveyCompareArray[] = [
                'target' => null,
                'source' => $questionId,
                'status' => 'missing',
            ];
        }

        return $surveyCompareArray;
    }

    /**
     * Get the complete comparison table
     *
     * @return \MUtil_Html Form nodes
     */
    public function getSurveyCompareTable($bridge) {
        $post = $this->formData;

        $element = $bridge->getForm()->createElement('html', 'table');
        $table   = $element->table(['class' => 'browser table']);

        $bridge->addElement($element);

        $table->caption($this->getTitle());

        $headers = [
            $this->_('Question code'),
            $this->_('Source Survey'),
            $this->_('Target Survey'),
        ];

        $tableHeader = $table->thead()->tr();

        foreach ($headers as $label) {
            $tableHeader->th($label);
        }

        $tableBody = $table->tbody();

        if ($this->sourceSurveyId && $this->targetSurveyId) {
            $row = $tableBody->tr();
            $row->td($this->_('Usage'));
            $row->td($this->getSurveyStatistics($this->sourceSurveyId));
            $row->td($this->getSurveyStatistics($this->targetSurveyId));

            $this->addSurveyCompareForm($tableBody, $post);
        }
    }

    /**
     * Gets question information about the survey structure from a specific survey and makes the result readable
     *
     * @param $surveyId int ID of the survey
     * @return array List of survey information
     */
    public function getSurveyData($surveyId) {
        $tracker = $this->loader->getTracker();

        $survey = $tracker->getSurvey($surveyId);

        $surveyInformation = $survey->getQuestionInformation($this->locale);

        $filteredSurveyInformation = $surveyInformation;
        foreach ($surveyInformation as $questionCode => $questionInfo) {
            if ($questionInfo['class'] == 'question_sub') {
                $parentCode                                           = $questionInfo['title'];
                $parent                                               = $surveyInformation[$parentCode];
                $filteredSurveyInformation[$questionCode]['question'] = $parent['question'] . ' | ' . $questionInfo['question'];
                if (isset($filteredSurveyInformation[$parentCode])) {
                    unset($filteredSurveyInformation[$parentCode]);
                }
            }
        }

        return $filteredSurveyInformation;
    }

    /**
     * Get Survey name from Id
     *
     * @param $surveyId
     * @return string Survey name
     */
    public function getSurveyName($surveyId) {
        $tracker = $this->loader->getTracker();
        $survey  = $tracker->getSurvey($surveyId);

        return $survey->getName();
    }   
    
    /**
     * Get the form select with all the questions in the survey and the current selected one
     *
     * @param $surveyData array List of survey data
     * @param $currentQuestionCode string Current selected question code
     * @param $targetQuestionCode string the target question code used in the name field of the select
     * @return \MUtil_Html Select object
     */
    public function getSurveyQuestionSelect($surveyData, $currentQuestionCode, $targetQuestionCode) {
        $name = 'target[' . $targetQuestionCode . ']';
        if ($targetQuestionCode === null) {
            $name = 'notfound[]';
        }

        $select = \MUtil_Html::create()->select(['name' => $name]);

        $empty = $this->util->getTranslated()->getEmptyDropdownArray();
        $select->option(reset($empty), ['value' => '']);

        foreach ($surveyData as $questionCode => $questionData) {
            $attributes = ['value' => $questionCode];
            if ($currentQuestionCode === $questionCode) {
                $attributes['selected'] = 'selected';
            }

            $select->option($questionData['question'], $attributes);
        }
        return $select;
    }

    /**
     * Creates a table showing the results of the survey compare
     *
     * @param $post array List of POST data
     * @return \MUtil_Html Table
     */
    public function getSurveyResults($post) {
        $comments = $this->getComments();

        $table  = \MUtil_Html::create()->table(['class' => 'browser table']);
        $header = $table->thead()->tr();
        $header->th($this->surveys[$this->sourceSurveyId]);
        $header->th($this->surveys[$this->targetSurveyId]);

        $tableBody = $table->tbody();
        $row       = $tableBody->tr();
        $row->td($this->getSurveyStatistics($this->sourceSurveyId));
        $row->td($this->getSurveyStatistics($this->targetSurveyId));
        $tableBody->tr()->td(['colspan' => 2]);

        if ($this->formData['copy_answers'] == 1) {
            $sourceSurveyData = $this->getSurveyData($this->sourceSurveyId);
            $targetSurveyData = $this->getSurveyData($this->targetSurveyId);

            $compareResultSummary = $this->getCompareResultSummary($post, $sourceSurveyData, $targetSurveyData);
            
            $tableBody->tr()->th($this->_('Summary'), ['colspan' => 2]);
            $tableBody->tr()->td($compareResultSummary, ['colspan' => 2]);           
        }

        if ($comments) {
            $tableBody->tr()->th($this->_('Comments'), ['colspan' => 2]);
            $tableBody->tr()->td($comments, ['colspan' => 2]);
        }

        return $table;
    }

/**
     * Create a select element node with all available surveys
     *
     * @param $name string name of the survey select
     * @return mixed \MUtil_Html node
     */
    public function getSurveySelect($name, $post) {
        $surveys = $this->surveys;

        $select = \MUtil_Html::create()->select(['name' => $name]);

        $empty = $this->util->getTranslated()->getEmptyDropdownArray();
        $select->option(reset($empty), ['value' => '']);

        if (!empty($surveys)) {
            foreach ($surveys as $surveyId => $surveyName) {
                $attributes = ['value' => $surveyId];
                if (isset($post[$name]) && $post[$name] == $surveyId) {
                    $attributes['selected'] = 'selected';
                }
                $select->option($surveyName, $attributes);
            }
        }

        return $select;
    }

    /**
     * Creates a small html block of number of answers and usage in tracks of surveys
     *
     * @param $surveyId int id of the survey
     * @return \MUtil_Html_Sequence
     */
    public function getSurveyStatistics($surveyId) {
        $seq   = new \MUtil_Html_Sequence();
        $seq->setGlue(\MUtil_Html::create('br'));
        $seq[] = $this->getNumberOfAnswers($surveyId);
        $seq[] = $this->calculateTrackUsage($surveyId);

        return $seq;
    }

    /**
     * Get the table structure from a survey database
     *
     * @param $surveyId int Survey ID
     * @return array List of table structure
     */
    public function getSurveyTableStructure($surveyId) {
        $tracker = $this->loader->getTracker();
        $survey  = $tracker->getSurvey($surveyId);
        $source  = $survey->getSource();

        $structure = $source->getSurveyTableStructure($survey->getSourceSurveyId());

        return $structure;
    }    
    
    /**
     * Get all available surveys
     *
     * @return array Survey Id => Survey name of available surveys
     */
    public function getSurveys() {
        if (!$this->surveys) {
            $dbLookup = $this->util->getDbLookup();

            $this->surveys = $dbLookup->getSurveysWithSid();
        }

        return $this->surveys;
    }

    /**
     * @return string Translated form title
     */
    public function getTitle() {
        return $this->_('Insert answers into a new version of a survey');
    }

    /**
     * Get the table structure of the survey token table
     *
     * @param $surveyId int Gens Survey ID
     * @return array List of token table structure
     */
    public function getTokenTableStructure($surveyId) {
        $tracker = $this->loader->getTracker();
        $survey  = $tracker->getSurvey($surveyId);
        $source  = $survey->getSource();

        $structure = $source->getTokenTableStructure($survey->getSourceSurveyId());

        return $structure;
    }
    
    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getUpdateBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('survey_replace_' . $this->sourceSurveyId . '_' . $this->targetSurveyId);

        $targetFields = array_key_exists('target', $this->formData) ? $this->formData['target'] : [];
        $batch->setVariable('targetFields', $targetFields);
        $batch->setVariable('sourceSurveyId', $this->sourceSurveyId);
        $batch->setVariable('sourceSurveyName', $this->getSurveyName($this->sourceSurveyId));
        $batch->setVariable('targetSurveyId', $this->targetSurveyId);
        $batch->setVariable('targetSurveyName', $this->getSurveyName($this->targetSurveyId));
        
        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            if ($this->formData['track_replace'] == 1) {
                $batch->addTask('Survey\\TrackReplaceTask');
            }
            
            if ($this->formData['token_update'] == 1) {
                $batch->addTask('Survey\\TokenReplaceTask');
            }

            if ($this->formData['copy_answers'] == 1) {
                $userId = $this->loader->getCurrentUser()->getUserId();
                $batch->addTask('Survey\\MoveAnswersTask', $userId);
            }
            
        }

        return $batch;
    }
    
     /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData() {
        if ($this->request->isPost()) {
            $this->formData = $this->request->getPost() + $this->formData;
        } else {
            foreach ($this->model->getColNames('default') as $name) {
                if (!(isset($this->formData[$name]) && $this->formData[$name])) {
                    $this->formData[$name] = $this->model->get($name, 'default');
                }
            }
        }
        if (!(isset($this->formData['import_id']) && $this->formData['import_id'])) {
            $this->formData['import_id'] = mt_rand(10000, 99999) . time();
        }

        if (array_key_exists('source_survey', $this->formData) && $this->formData['source_survey']) {
            $this->sourceSurveyId = $this->formData['source_survey'];
        }
        if (array_key_exists('target_survey', $this->formData) && $this->formData['target_survey']) {
            $this->targetSurveyId = $this->formData['target_survey'];
        }

        $this->_session = new \Zend_Session_Namespace(__CLASS__ . '-' . $this->formData['import_id']);

        if (array_key_exists('target', $this->formData)) {
            $this->_session->target = $this->formData['target'];
        } else {
            if (isset($this->_session->target)) {
                $this->formData['target'] = $this->_session->target;
            }
        }
    }

    /**
     * Run a query on the survey source database
     *
     * @param $surveyId int Gems Survey ID
     * @param $sql SQL statement to run on source database
     */
    protected function querySurveySource($surveyId, $sql) {
        $tracker        = $this->loader->getTracker();
        $survey         = $tracker->getSurvey($surveyId);
        $sourceSurveyId = $survey->getSourceSurveyId();
        $source         = $survey->getSource();

        $source->lsDbQuery($sourceSurveyId, $sql);
    }

    /**
     * Inserts survey answers from the source survey into the target survey
     *
     * @param $post array List of POST data
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     */
    public function setSurveyAnswersToNewSurvey($post, $sourceSurveyData, $targetSurveyData) {
        $sql = $this->buildSurveyQuery($post, $sourceSurveyData, $targetSurveyData);
        $this->querySurveySource($this->targetSurveyId, $sql);
    }

    /**
     * Inserts survey tokens from the source survey into the target survey
     *
     * @param $post array List of POST data
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     */
    public function setSurveyTokensToNewSurvey($post, $sourceSurveyData, $targetSurveyData) {
        $sql = $this->buildTokenQuery($post, $sourceSurveyData, $targetSurveyData);
        $this->querySurveySource($this->targetSurveyId, $sql);
    }

    /**
     * Adds whitespaces to an SQL query, so it'll look more readable on screen
     *
     * @param $sql string SQL statement
     * @return string SQL statement with white spaces
     */
    public function whitespaceQuery($sql) {
        return str_replace([', ', '(SELECT ', ' FROM '], [", \n", "\n(SELECT \n", "\n FROM "], $sql);
    }
}
