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

use Gems\Repository\OrganizationRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\TrackEvents;

/**
 *
 * @package    Gems
 * @subpackage Task\Respondent
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 12-Mar-2019 17:47:50
 */
class UpdateRespondentTask extends \MUtil\Task\TaskAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($respId = null, $orgId = null)
    {
        $batch                  = $this->getBatch();
        /**
         * @var OrganizationRepository $organizationRepository
         */
        $organizationRepository = $batch->getVariable('organizationRepository');
        /**
         * @var RespondentRepository $respondentRepository
         */
        $respondentRepository   = $batch->getVariable('respondentRepository');
        /**
         * @var TrackEvents $trackEvents
         */
        $trackEvents            = $batch->getVariable('trackEvents');

        $org                    = $organizationRepository->getOrganization($orgId);

        $changeEventClass = $org->getRespondentChangeEventClass();

        if ($changeEventClass) {
            $event = $trackEvents->loadRespondentChangedEvent($changeEventClass);

            if ($event) {
                $respondent = $respondentRepository->getRespondent(null, $orgId, $respId);

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
                        $this->_('%d respondents checked, %d respondents were changed.'),
                        $batch->getCounter('respondentsChecked'),
                        $batch->getCounter('respondentsChanged')
                        ));
    }
}
