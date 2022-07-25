<?php

/**
 *
 * @package    Gems
 * @subpackage task_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 9-okt-2014 13:18:02
 */
class RecalculateFields extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($respTrackData = null)
    {
        $batch     = $this->getBatch();
        $tracker   = $this->loader->getTracker();
        $respTrack = $tracker->getRespondentTrack($respTrackData);

        $fieldsChanged = false;
        $tokensChanged = $respTrack->recalculateFields($fieldsChanged);

        $t = $batch->addToCounter('trackFieldsChecked');
        if ($fieldsChanged) {
            $i = $batch->addToCounter('trackFieldsChanged');
        } else {
            $i = $batch->getCounter('trackFieldsChanged');
        }
        if ($tokensChanged) {
            $j = $batch->addToCounter('trackTokensChanged');
        } else {
            $j = $batch->getCounter('trackTokensChanged');
        }

        $batch->setMessage(
                'trackFieldsCheck',
                sprintf($this->_('%d tracks checked, %d fields changed, %d token changed.'), $t, $i, $j));
    }
}
