<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

use Gems\Cache\HelperAdapter;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 21, 2016 2:09:59 PM
 */
class FinishTrackImport extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var HelperAdapter
     */
    protected $cache;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

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
    public function execute()
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (! (isset($import['trackId']) && $import['trackId'])) {
            // Do nothing
            return;
        }

        $tracker     = $this->loader->getTracker();
        $trackEngine = $tracker->getTrackEngine($import['trackId']);
        $trackEngine->updateRoundCount($this->currentUser->getUserLoginId());

        // Now cleanup the cache
        if ($this->cache instanceof HelperAdapter) {
            $this->cache->invalidateTags(['tracks']);
        }
    }
}
