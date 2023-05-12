<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Merge;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 1, 2016 7:52:13 PM
 */
class DeactivateTrackFieldTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($roundId = null, $roundDescription = null)
    {
        $batch  = $this->getBatch();

        if ($batch->hasVariable('trackEngine')) {
            $trackEngine = $batch->getVariable('trackEngine');
            if ($trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {

                $model = $trackEngine->getRoundModel(true, 'delete');

                $roundData['gro_id_round'] = $roundId;
                $roundData['gro_active']   = 0;
                $roundData = $model->save($roundData);

                $batch->addMessage(sprintf(
                        $this->_('Deactivated round %s.'),
                        ($roundDescription ? $roundDescription : $roundId)
                        ));
            }
        }
    }
}
