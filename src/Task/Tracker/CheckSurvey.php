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

use Gems\Tracker;
use Gems\User\User;

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class CheckSurvey extends \MUtil\Task\TaskAbstract
{
    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $sourceSurveyId = null, $surveyId = null, $userId = null)
    {
        $batch    = $this->getBatch();
        $source   = $this->tracker->getSource($sourceId);

        if (null === $userId) {
            $userId = $this->currentUser->getUserId();
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
