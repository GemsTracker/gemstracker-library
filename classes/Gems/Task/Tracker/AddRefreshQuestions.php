<?php

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

use Gems\Tracker\TrackerInterface;
use Exception;
use MUtil\Html\HtmlInterface;

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class AddRefreshQuestions extends \MUtil\Task\TaskAbstract
{
    /**
     * The \Gems DB
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * @var TrackerInterface
     */
    protected $tracker;

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
            $survey = $this->tracker->getSurvey($surveyId);
        } else {
            $survey = $this->tracker->getSurveyBySourceId($sourceSurveyId, $sourceId);
        }

        if (! $survey->isActive()) {
            return;
        }

        // Now save the questions
        $answerModel = $survey->getAnswerModel('en');
        
        $hash = $survey->calculateHash();
        
        if ($survey->getHash() === $hash) {
            return;
        }
        
        $survey->setHash($hash, $this->loader->getCurrentUser()->getUserId());

        foreach ($answerModel->getItemsOrdered() as $order => $name) {
            if (true === $answerModel->get($name, 'survey_question')) {
                $batch->addTask('Tracker\\RefreshQuestion', $surveyId, $name, $order);
            }
        }

        // If we have a response database, create a view on the answers
        if ($this->project->hasResponseDatabase()) {
            $this->replaceCreateView($survey, $answerModel);
        }
    }
    
    protected function render($result)
    {
        if ($result instanceof HtmlInterface) {
            $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $view = $viewRenderer->view;

            $result = $result->render($view);
        }

        return $result;
    }

    /**
     * Get a name for the view
     *
     * @param \Gems\Tracker\Survey $survey
     * @return string
     */
    protected function getViewName(\Gems\Tracker\Survey $survey)
    {
        return 'T' . $survey->getSurveyId();
    }

    /**
     * Handles creating or replacing the view for this survey
     *
     * @param \Gems\Tracker\Survey       $viewName
     * @param \MUtil\Model\ModelAbstract $answerModel
     */
    protected function replaceCreateView(\Gems\Tracker\Survey $survey, \MUtil\Model\ModelAbstract $answerModel)
    {
        $viewName = $this->getViewName($survey);
        $responseDb = $this->project->getResponseDatabase();
        $fieldSql   = '';

        foreach ($answerModel->getItemsOrdered() as $name) {
            if (true === $answerModel->get($name, 'survey_question') && // It should be a question
                    !in_array($name, ['submitdate', 'startdate', 'datestamp']) && // Leave out meta info
                    !$answerModel->is($name, 'type', \MUtil\Model::TYPE_NOVALUE)) {         // Only real answers
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