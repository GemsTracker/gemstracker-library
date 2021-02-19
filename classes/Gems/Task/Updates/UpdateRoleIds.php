<?php

/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3  19-feb-2014 20:42:40
 * @deprecated since version 1.9.1 After cleanup of old version upgrades            
 */
class Gems_Task_Updates_UpdateRoleIds extends \MUtil_Task_TaskAbstract
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
     */
    public function execute()
    {
        $role    = \Gems_Roles::getInstance();
        $parents = $this->db->fetchPairs("SELECT grl_id_role, grl_parents FROM gems__roles");
        
        // \MUtil_Echo::track($parents);
        if ($parents) {
            foreach ($parents as $id => $priv) {
                $values['grl_parents'] = implode(',', $role->translateToRoleIds($priv));

                $this->db->update('gems__roles', $values, $this->db->quoteInto('grl_id_role = ?', $id));
            }
        }
    }
}
