<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 02-Sep-2019 17:26:07
 */
class Gems_Default_SystemUserAction extends \Gems_Default_StaffAction
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = [
        'columns' => 'getBrowseColumns',
        'extraFilter' => [[
            'gsf_is_embedded' => 1,
            'gsf_logout_on_survey' => 1,
            ]],
        'menuActionController' => 'system-user',
        ];

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = [
        'routeAction' => 'show',
        ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Staff\\SystemUserCreateEditSnippet';

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Staff\SystemUserSearchSnippet');

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = [
        'selectedUser' => 'getSelectedUser',
        ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'User\\EmbeddedUserTestUrlForm',
        ];

    /**
     * Snippets for mail
     *
     * @var mixed String or array of snippets name
     */
    protected $switchSnippets = array('Staff\\StaffCreateEditSnippet');

    /**
     * True for staff model, otherwise system user model
     *
     * @var boolean
     */
    protected $useStaffModel = false;

    /**
     *
     * @return array
     */
    public function getBrowseColumns()
    {
        $br = \MUtil_Html::create('br');

        return [
            10 => array('gsf_login', $br, 'gsf_last_name'),
            20 => array('gsf_id_organization', $br, 'gsf_id_primary_group'),
            30 => array('gsf_is_embedded', $br, 'gsf_logout_on_survey'),
            40 => array('gsf_iso_lang', $br, 'gsf_active'),
            ];
    }

    /**
     * Helper function to get the title for the deactivate action.
     *
     * @return $string
     */
    public function getDeactivateTitle()
    {
        $user = $this->getSelectedUser();

        if ($user) {
            return sprintf($this->_('Deactivate system user %s'), $user->getLoginName());
        }

        return parent::getDeactivateTitle();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('System users');
    }

    /**
     * Helper function to get the title for the reactivate action.
     *
     * @return $string
     */
    public function getReactivateTitle()
    {
        $user = $this->getSelectedUser();

        if ($user) {
            return sprintf($this->_('Reactivate system user %s'), $user->getLoginName());
        }

        return parent::getReactivateTitle();
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        if (isset($filter['specials'])) {
            if ($filter['specials']) {
                $filter[$filter['specials']] = 1;
            }
            unset($filter['specials']);
        }

        return $filter;
    }

    /**
     * Helper function to get the title for the show action.
     *
     * @return $string
     */
    public function getShowTitle()
    {
        $user = $this->getSelectedUser();

        if ($user) {
            return sprintf($this->_('Show system user %s'), $user->getLoginName());
        }

        return $this->_('System users');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('system user', 'system users', $count);
    }
}
