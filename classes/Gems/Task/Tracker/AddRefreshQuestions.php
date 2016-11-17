<?php

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Task_Tracker_AddRefreshQuestions extends \MUtil_Task_TaskAbstract
{
    /**
     * The Gems DB
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $sourceSurveyId = null, $surveyId = null)
    {
        $batch = $this->getBatch();

        if ($surveyId) {
            $survey = $this->loader->getTracker()->getSurvey($surveyId);
        } else {
            $survey = $this->loader->getTracker()->getSurveyBySourceId($sourceSurveyId, $sourceId);
        }

        if (! $survey->isActive()) {
            return;
        }

        // Now save the questions
        $answerModel = $survey->getAnswerModel('en');

        foreach ($answerModel->getItemsOrdered() as $order => $name) {
            if (true === $answerModel->get($name, 'survey_question')) {
                $batch->addTask('Tracker_RefreshQuestion', $surveyId, $name, $order);
            }
        }

        // If we have a response database, create a view on the answers
        if ($this->project->hasResponseDatabase()) {
            $this->replaceCreateView($survey, $answerModel);
        }
    }

    /**
     * Get a name for the view
     *
     * @param \Gems_Tracker_Survey $survey
     * @return string
     */
    protected function getViewName(\Gems_Tracker_Survey $survey)
    {
        return 'T' . $survey->getSurveyId();
    }

    /**
     * Handles creating or replacing the view for this survey
     *
     * @param \Gems_Tracker_Survey       $viewName
     * @param \MUtil_Model_ModelAbstract $answerModel
     */
    protected function replaceCreateView(\Gems_Tracker_Survey $survey, \MUtil_Model_ModelAbstract $answerModel)
    {
        $viewName = $this->getViewName($survey);
        $responseDb = $this->project->getResponseDatabase();
        $fieldSql   = '';

        foreach ($answerModel->getItemsOrdered() as $name) {
            if (true === $answerModel->get($name, 'survey_question') && // It should be a question
                    !in_array($name, array('submitdate', 'startdate', 'datestamp')) && // Leave out meta info
                    !$answerModel->is($name, 'type', \MUtil_Model::TYPE_NOVALUE)) {         // Only real answers
                $fieldSql .= ',MAX(IF(gdr_answer_id = ' . $responseDb->quote($name) . ', gdr_response, NULL)) AS ' . $responseDb->quoteIdentifier($name);
            }
        }

        if ($fieldSql > '') {
            $dbConfig = $this->db->getConfig();
            $tokenTable = $this->db->quoteIdentifier($dbConfig['dbname'] . '.gems__tokens');
            $createViewSql = 'CREATE OR REPLACE VIEW ' . $responseDb->quoteIdentifier($viewName) . ' AS SELECT gdr_id_token';
            $createViewSql .= $fieldSql;
            $createViewSql .= "FROM gemsdata__responses join " . $tokenTable .
                    " on (gto_id_token=gdr_id_token and gto_id_survey=" . $survey->getSurveyId() .
                    ") GROUP BY gdr_id_token;";
            try {
                $responseDb->query($createViewSql)->execute();
            } catch (Exception $exc) {
                $responseConfig = $responseDb->getConfig();
                $dbUser = $this->db->quoteIdentifier($responseConfig['username']) . '@' .
                        $this->db->quoteIdentifier($responseConfig['host']);
                $statement = "GRANT SELECT ON  " . $tokenTable . " TO " . $dbUser;

                $batch = $this->getBatch();
                $batch->addMessage(sprintf(
                        $this->_("View creation failed for survey %s with message: '%s'"),
                        $survey->getName(),
                        $exc->getMessage()
                        ));
                $batch->addMessage(sprintf($this->_("View creation statement: %s"), $createViewSql));
                $batch->addMessage(sprintf($this->_("Try adding rights using this statement: %s"), $statement));
            }
        }
    }

}