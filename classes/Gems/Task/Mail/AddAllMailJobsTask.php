<?php

/**
 * Description of AddAllMailJobsTask
 *
 * @package    Gems
 * @subpackage Task
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.3
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
class AddAllMailJobsTask extends \MUtil_Task_TaskAbstract {

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
     */
    public function execute() {
        $sql = "SELECT *
            FROM gems__comm_jobs INNER JOIN
                gems__comm_templates ON gcj_id_message = gct_id_template
            WHERE gcj_active = 1
            ORDER BY CASE WHEN gcj_id_survey IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_round_description IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_track IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_organization IS NULL THEN 1 ELSE 0 END";

        $jobs = $this->db->fetchAll($sql);

        if ($jobs) {
            $batch = $this->getBatch();
            foreach ($jobs as $job) {
                $batch->addTask('Mail\\ExecuteMailJobTask', $job);
            }
        } else {
            $this->getBatch()->addMessage($this->_('Nothing to do, please create a mailjob first.'));
        }
    }

}
