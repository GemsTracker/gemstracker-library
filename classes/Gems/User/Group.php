<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User;

/**
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Dec 23, 2016 4:01:38 PM
 */
class Group extends \Gems_Registry_CachedArrayTargetAbstract
{
    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array('group');

    /**
     * The default organization data for 'no organization'.
     *
     * @var array
     */
    protected $_noGroup = array(
        'ggp_id_group'           => 0,
        'ggp_name'               => 'NO GROUP',
        'ggp_description'        => 'NO GROUP',
        'ggp_role'               => 'nologin',
        'ggp_may_set_groups'     => '',
        'ggp_group_active'       => 0,
        'ggp_staff_members'      => 0,
        'ggp_respondent_members' => 0,
        'ggp_allowed_ip_ranges'  => '',
        );

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Get the group id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->_get('ggp_id_group');
    }

    /**
     * Get the group name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_get('ggp_name');
    }

    /**
     * Get the role name.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->_get('ggp_role');
    }

    /**
     * Is the group active?
     *
     * @return boolean
     */
    public function isActive()
    {
        return (boolean) $this->_get('ggp_group_active');
    }

    /**
     * Load the data when the cache is empty.
     *
     * @param mixed $id
     * @return array The array of data values
     */
    protected function loadData($id)
    {
        try {
            $sql = "SELECT * FROM gems__groups WHERE ggp_id_group = ? LIMIT 1";
            $data = $this->db->fetchRow($sql, intval($id));
        } catch (\Exception $e) {
            $data = false;
        }
        if (! $data) {
            $data = $this->_noGroup;
        }

        // Translate numeric role id
        if (is_array($data) && intval($data['ggp_role'])) {
            $data['ggp_role'] = \Gems_Roles::getInstance()->translateToRoleName($data['ggp_role']);
        }

        return $data;
    }
}
