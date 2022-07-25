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

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class RefreshQuestion extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($surveyId = null, $questionId = null, $order = null)
    {
        $batch  = $this->getBatch();
        $survey = $this->loader->getTracker()->getSurvey($surveyId);

        // Now save the questions
        $answerModel   = $survey->getAnswerModel('en');
        $questionModel = new \MUtil\Model\TableModel('gems__survey_questions');

        \Gems\Model::setChangeFieldsByPrefix($questionModel, 'gsq');

        $label = $answerModel->get($questionId, 'label');
        /*
        if ($label instanceof \MUtil\Html\HtmlInterface) {
            $label = $label->render($this->view);
        }
        // */

        $question['gsq_id_survey']   = $surveyId;
        $question['gsq_name']        = $questionId;
        $question['gsq_name_parent'] = $answerModel->get($questionId, 'parent_question');
        $question['gsq_order']       = $order;
        $question['gsq_type']        = $answerModel->getWithDefault($questionId, 'type', \MUtil\Model::TYPE_STRING);
        $question['gsq_class']       = $answerModel->get($questionId, 'thClass');
        $question['gsq_group']       = $answerModel->get($questionId, 'group');
        $question['gsq_label']       = $label;
        $question['gsq_description'] = $answerModel->get($questionId, 'description');

        // \MUtil\EchoOut\EchoOut::track($question);
        try {
            $questionModel->save($question);
        } catch (\Exception $e) {
            $batch->addMessage(sprintf(
                    $this->_('Save failed for survey %s, question %s: %s'),
                    $survey->getName(),
                    $questionId,
                    $e->getMessage()
                    ));
        }

        $batch->addToCounter('checkedQuestions');
        if ($questionModel->getChanged()) {
            $batch->addToCounter('changedQuestions');
        }
        $batch->setMessage('questionschanged', sprintf(
                $this->_('%d of %d questions changed.'),
                $batch->getCounter('changedQuestions'),
                $batch->getCounter('checkedQuestions')));

        $options = $answerModel->get($questionId, 'multiOptions');
        if ($options) {
            $optionModel   = new \MUtil\Model\TableModel('gems__survey_question_options');
            \Gems\Model::setChangeFieldsByPrefix($optionModel, 'gsqo');

            $option['gsqo_id_survey'] = $surveyId;
            $option['gsqo_name']      = $questionId;
            $i = 0;

            // \MUtil\EchoOut\EchoOut::track($options);
            foreach ($options as $key => $label) {
                $option['gsqo_order'] = $i;
                $option['gsqo_key']   = $key;
                $option['gsqo_label'] = $label;

                try {
                    $optionModel->save($option);
                } catch (\Exception $e) {
                    $batch->addMessage(sprintf(
                            $this->_('Save failed for survey %s, question %s, option "%s" => "%s": %s'),
                            $survey->getName(),
                            $questionId,
                            $key,
                            $label,
                            $e->getMessage()
                            ));
                }

                $i++;
            }
            $batch->addToCounter('checkedOptions', count($options));
            $batch->addToCounter('changedOptions', $optionModel->getChanged());
            $batch->setMessage('optionschanged', sprintf(
                    $this->_('%d of %d options changed.'),
                    $batch->getCounter('changedOptions'),
                    $batch->getCounter('checkedOptions')));
        }

    }
}
