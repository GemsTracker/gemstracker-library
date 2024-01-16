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
class Group extends \Gems\Registry\CachedArrayTargetAbstract
{
    /**
     * No two factor always allowed
     */
    CONST NO_TWO_FACTOR_ALLOWED = 0;

    /**
     * No two factor only allowed inside ip range
     */
    CONST NO_TWO_FACTOR_INSIDE_ONLY = 99;

    /**
     * Two factor setup required when inside ip range
     */
    // CONST NO_TWO_FACTOR_SETUP_INSIDE = 50;

    /**
     * Two factor setup required when loggin in
     */
    // CONST NO_TWO_FACTOR_SETUP_OUTSIDE = 25;

    /**
     * Two factor disabled when set
     */
    CONST TWO_FACTOR_SET_DISABLED = 0;

    /**
     * Two factor required when outside ip range
     */
    CONST TWO_FACTOR_SET_OUTSIDE_ONLY = 50;

    /**
     * Two factor required when set
     */
    CONST TWO_FACTOR_SET_REQUIRED = 99;

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
        'ggp_member_type'        => '',
        'ggp_allowed_ip_ranges'  => '',
        'ggp_mask_settings'      => array(),
        );

    /**
     *
     * @var \Gems\Screens\BrowseScreenInterface
     */
    protected $_respondentBrowseScreen;

    /**
     *
     * @var \Gems\Screens\EditScreenInterface
     */
    protected $_respondentEditScreen;

    /**
     *
     * @var \Gems\Screens\ShowScreenInterface
     */
    protected $_respondentShowScreen;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Set in child classes
     *
     * @var string Name of table used in gtrs_table
     */
    protected $translationTable = 'gems__groups';
    
    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Return default new use group, if it exists
     *
     * @return string
     */
    public function getDefaultNewStaffGroup()
    {
        return $this->_get('ggp_default_group');
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
     *
     * @return \Gems\Screens\BrowseScreenInterface
     */
    public function getRespondentBrowseScreen()
    {
        if ($this->_respondentBrowseScreen || (! $this->_get('ggp_respondent_browse'))) {
            return $this->_respondentBrowseScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_respondentBrowseScreen = $screenLoader->loadRespondentBrowseScreen($this->_get('ggp_respondent_browse'));

        return $this->_respondentBrowseScreen;
    }

    /**
     *
     * @return \Gems\Screens\EditScreenInterface
     */
    public function getRespondentEditScreen()
    {
        if ($this->_respondentEditScreen || (! $this->_get('ggp_respondent_edit'))) {
            return $this->_respondentEditScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_respondentEditScreen = $screenLoader->loadRespondentEditScreen($this->_get('ggp_respondent_edit'));

        return $this->_respondentEditScreen;
    }

    /**
     *
     * @return \Gems\Screens\ShowScreenInterface
     */
    public function getRespondentShowScreen()
    {
        if ($this->_respondentShowScreen || (! $this->_get('ggp_respondent_show'))) {
            return $this->_respondentShowScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_respondentShowScreen = $screenLoader->loadRespondentShowScreen($this->_get('ggp_respondent_show'));

        return $this->_respondentShowScreen;
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
     * Is this a staff group
     *
     * @return boolean
     */
    public function isStaff()
    {
        return $this->_get('ggp_member_type') === 'staff';
    }

    /**
     * Should a user be authorized using two factor authentication?
     *
     * @param string $ipAddress
     * @param boolean $hasKey
     * @return boolean
     */
    public function isTwoFactorRequired($ipAddress, $hasKey)
    {
        // \MUtil\EchoOut\EchoOut::track($ipAddress, $hasKey, $this->_get('ggp_2factor_set'), $this->_get('ggp_2factor_not_set'));

        if ($hasKey) {
            switch ($this->_get('ggp_2factor_set')) {
                case self::TWO_FACTOR_SET_REQUIRED:
                    return true;

                case self::TWO_FACTOR_SET_OUTSIDE_ONLY:
                    // Required when not in range
                    $noTwoFactorIpRanges = $this->_get('ggp_no_2factor_ip_ranges');
                    if (empty($noTwoFactorIpRanges)) {
                        return true;
                    }
                    return ! $this->util->isAllowedIP($ipAddress, $noTwoFactorIpRanges);
            }
        } else {
            switch ($this->_get('ggp_2factor_not_set')) {
                case self::NO_TWO_FACTOR_INSIDE_ONLY:
                    // Required even when not set when not in range
                    $noTwoFactorIpRanges = $this->_get('ggp_no_2factor_ip_ranges');
                    if (empty($noTwoFactorIpRanges)) {
                        return true;
                    }
                    return ! $this->util->isAllowedIP($ipAddress, $noTwoFactorIpRanges);
            }
        }

        return false;
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

        return $data;
    }
}
