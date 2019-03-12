<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Respondent;

/**
 *
 * @package    Gems
 * @subpackage Task\Respondent
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 12-Mar-2019 17:47:50
 */
class UpdateRespondentTask extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($respId = null, $orgId = null)
    {
        $batch = $this->getBatch();
        $org   = $this->loader->getOrganization($orgId);

        $changeEventClass = $org->getRespondentChangeEventClass();

        if ($changeEventClass) {
            $event = $this->loader->getEvents()->loadRespondentChangedEvent($changeEventClass);

            if ($event) {
                $respondent = $this->loader->getRespondent(null, $orgId, $respId);

                $batch->addToCounter('respondentsChecked');
                if ($respondent->getReceptionCode()->isSuccess() &&
                        $event->processChangedRespondent($respondent)) {

                    $batch->addToCounter('respondentsChanged');
                }

            }
        }

        $batch->setMessage(
                'respCheck',
                sprintf(
                        $this->_('%d respondents checked, %d respondent were changed.'),
                        $batch->getCounter('respondentsChecked'),
                        $batch->getCounter('respondentsChanged')
                        ));
    }
}
