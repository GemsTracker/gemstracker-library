<?php

/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Db;

/**
 *
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class UpdatePatchLevel extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param int $patchLevel Only execute patches for this patchlevel
     */
    public function execute($patchLevel = null)
    {
        //Update the patchlevel only when we have executed at least one patch
        $batch = $this->getBatch();
        if ($batch->getCounter('executed')) {
            $this->db->query(
                    'INSERT IGNORE INTO gems__patch_levels (gpl_level, gpl_created) VALUES (?, CURRENT_TIMESTAMP)',
                    $patchLevel
                    );
        }

    }
}
