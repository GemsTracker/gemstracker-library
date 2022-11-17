<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

use Gems\Condition\ConditionLoader;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4.
 */
class CheckTrackRoundConditionImportTask extends \MUtil\Task\TaskAbstract
{
    /**
     * @var ConditionLoader
     */
    protected $conditionLoader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($lineNr = null, $conditionData = null)
    {
        $batch      = $this->getBatch();
        $import     = $batch->getVariable('import');

        if (isset($conditionData['gcon_id']) && $conditionData['gcon_id']) {
            $import['importConditions'][$conditionData['gcon_id']] = false;
        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                $this->_('No gcon_id specified for condition at line %d.'),
                $lineNr
                ));
        }
        if (isset($conditionData['gcon_class']) && $conditionData['gcon_class']) {
            try {
                $this->conditionLoader->loadRoundCondition($conditionData['gcon_class']);
            } catch (\Gems\Exception\Coding $ex) {
                $batch->addToCounter('import_errors');
                $batch->addMessage(sprintf(
                        $this->_('Unknown or invalid round condition "%s" specified on line %d.'),
                        $conditionData['gcon_class'],
                        $lineNr
                        ));
            }
        }
        $batch->setVariable('import', $import);
    }
}
