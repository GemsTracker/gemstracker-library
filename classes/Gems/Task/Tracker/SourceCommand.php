<?php
/**
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Executes any command in a source for a given $sourceId
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 * @deprecated since 1.6.4 No longer in use
 */
class Gems_Task_Tracker_SourceCommand extends \MUtil_Task_TaskAbstract
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
    public function execute($sourceId = null, $command = null)
    {
        $batch  = $this->getBatch();
        $params = array_slice(func_get_args(), 2);
        $source = $this->loader->getTracker()->getSource($sourceId);

        if ($messages = call_user_func_array(array($source, $command), $params)) {
            foreach ($messages as $message) {
                $batch->addMessage($command . ': ' . $message);
            }
        }
    }
}