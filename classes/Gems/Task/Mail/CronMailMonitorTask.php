<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Mail;

/**
 *
 * @package    Gems
 * @subpackage Task\Mail
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 11-Jul-2017 15:47:54
 */
class CronMailMonitorTask extends \MUtil_Task_TaskAbstract
{
   /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     * Adds all jobs to the queue
     */
    public function execute()
    {
        $this->util->getMonitor()->startCronMailMonitor();
    }
}
