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
class Gems_Task_Tracker_CheckSurvey extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $sourceSurveyId = null, $surveyId = null, $userId = null)
    {
        $batch    = $this->getBatch();
        $source   = $this->loader->getTracker()->getSource($sourceId);

        if (null === $userId) {
            $userId = $this->loader->getCurrentUser()->getUserId();
       }

        $messages = $source->checkSurvey($sourceSurveyId, $surveyId, $userId);

        $batch->addToCounter('checkedSurveys');
        $batch->addToCounter('changedSurveys', $messages ? 1 : 0);
        $batch->setMessage('changedSurveys', sprintf(
                $this->_('%d of %d surveys changed.'),
                $batch->getCounter('changedSurveys'),
                $batch->getCounter('checkedSurveys')));

        if ($messages) {
            foreach ((array) $messages as $message) {
                $batch->addMessage($message);
            }
        }
    }
}
