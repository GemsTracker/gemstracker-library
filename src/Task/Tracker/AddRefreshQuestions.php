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

use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\ResponseDataRepository;
use Gems\Tracker\TrackerInterface;
use Exception;
use MUtil\Html\HtmlInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;

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
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    /**
     * The \Gems DB
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

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

        // Skip hash calculation as this is a rare function and we cannot trust there to be no changes
//        $hash = $survey->calculateHash();
//
//        if ($survey->getHash() === $hash) {
//            return;
//        }

        $survey->setHash($hash, $this->currentUserRepository->getCurrentUserId());
        $metaModel = $answerModel->getMetaModel();

        foreach ($metaModel->getItemsOrdered() as $order => $name) {
             if (true === $metaModel->get($name, 'survey_question')) {
                $batch->addTask('Tracker\\RefreshQuestion', $survey->getSurveyId(), $name, $order);
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
     * @param \Gems\Tracker\Survey $survey
     * @param DataReaderInterface $answerModel
     * @return void
     */
    protected function replaceCreateView(\Gems\Tracker\Survey $survey, DataReaderInterface $answerModel)
    {
        /**
         * @var ResponseDataRepository $repository
         */
        $repository = $this->overLoader->getContainer()->get(ResponseDataRepository::class);
        try {
            $repository->replaceCreateView($survey, $answerModel);
        } catch (Exception $exc) {
            $batch = $this->getBatch();
            $batch->addMessage(sprintf(
                    $this->_("View creation failed for survey %s with message: '%s'"),
                    $survey->getName(),
                    $exc->getMessage()
                    ));
        }
    }

}