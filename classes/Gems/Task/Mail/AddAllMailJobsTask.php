<?php

/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Mail;

/**
 * Description
 *
 * Long description
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.3
 */
class AddAllMailJobsTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * Adds all jobs to the queue
     *
     * @param $respondentId Optional, execute for just one respondent
     * @param $organizationId Optional, execute for just one organization
     */
    public function execute($respondentId = null, $organizationId = null, $forceSent = false)
    {
        $sql = "SELECT gcj_id_job, gcj_communication_type
            FROM gems__comm_jobs
            JOIN gems__comm_messengers ON gcj_communication_messenger = gcm_id_messenger
            WHERE gcj_active = 1";

        if ($organizationId) {
            $sql .= $this->db->quoteInto(
                    " AND (gcj_id_organization IS NULL OR gcj_id_organization = ?)",
                    $organizationId
                    );
        }
        if ($forceSent) {
            $sql .= " AND (gcj_filter_mode IN ('N', 'B'))";
        }

        $sql .= "
            ORDER BY gcj_id_order,
                CASE WHEN gcj_id_survey IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_round_description IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_track IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_organization IS NULL THEN 1 ELSE 0 END";

        $jobs = $this->db->fetchAll($sql);
        // \MUtil_Echo::track($sql, $jobs);

        $batch = $this->getBatch();
        if ($jobs) {
            foreach ($jobs as $job) {
                $batch->addTask('Comm\\ExecuteCommJobTask', $job['gcj_id_job'], $respondentId, $organizationId, false, $forceSent);
            }
        } else {
            $this->getBatch()->addMessage($this->_('Nothing to do, please create a mail job first.'));
        }
        // When manually running the jobs, we do not start the monitortask
        if ($batch->getId() == 'cron') {
            $batch->addTask('Mail\\CronMailMonitorTask');
        }
    }
}
