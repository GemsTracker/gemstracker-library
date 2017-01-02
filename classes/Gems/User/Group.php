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
        'ggp_mask_settings'      => array(),
        );

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\User\Mask\MaskStore
     */
    protected $maskStore;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->maskStore = $this->loader->getUserMaskStore();
        $this->maskStore->setMaskSettings($this->_data['ggp_mask_settings']);
    }

    /**
     *
     * @param array $row
     * @return array with the values masked
     */
    public function applyGroupToData(array $row)
    {
        return $this->maskStore->maskRow($row);
    }

    /**
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param boolean $hideWhollyMasked When true the labels of wholly masked items are removed
     * @return $this
     */
    public function applyGroupToModel(\MUtil_Model_ModelAbstract $model, $hideWhollyMasked)
    {
        $this->maskStore->applyMaskDataToModel($model, $hideWhollyMasked);

        return $this;
    }

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
     *
     * @param string $fieldName
     * @return boolean True if this field is invisible
     */
    public function isFieldInvisible($fieldName)
    {
        return $this->maskStore->isFieldInvisible($fieldName);
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial($fieldName)
    {
        return $this->maskStore->isFieldMaskedPartial($fieldName);
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole($fieldName)
    {
        return $this->maskStore->isFieldMaskedWhole($fieldName);
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
        if (is_array($data)) {
            if (intval($data['ggp_role'])) {
                $data['ggp_role'] = \Gems_Roles::getInstance()->translateToRoleName($data['ggp_role']);
            }
            if (! isset($data['ggp_mask_settings'])) {
                $data['ggp_mask_settings'] = array();
            } elseif (! is_array($data['ggp_mask_settings'])) {
                $data['ggp_mask_settings'] = (array) json_decode($data['ggp_mask_settings']);
            }
            // \MUtil_Echo::track($data['ggp_mask_settings']);
        }

        return $data;
    }
}
