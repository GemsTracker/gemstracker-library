<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_StaffAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Staff\\StaffTableSnippet';

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('staff');

    /**
     * The parameters used for the deactivate action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deactivateParameters = array('saveData' => array('gsf_active' => 0));

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Staff\StaffSearchSnippet');

    /**
     * The parameters used for the mail action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $mailParameters = array(
        'mailTarget'  => 'staff',
        'identifier'  => '_getIdParam',
        'routeAction' => 'show',
        'formTitle'   => 'getMailFormTitle',
        );

    /**
     * Snippets for mail
     *
     * @var mixed String or array of snippets name
     */
    protected $mailSnippets = array('Mail_MailFormSnippet');

    /**
     * The parameters used for the reactivate action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $reactivateParameters = array('saveData' => array('gsf_active' => 1));

    /**
     * The parameters used for the reset action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $resetParameters = array(
        'askOld'     => false,   // Do not ask for the old password
        'forceRules' => false,   // If user logs in using password that does not obey the rules, he is forced to change it
        'user' => 'getSelectedUser',
        );

    /**
     * Snippets for reset
     *
     * @var mixed String or array of snippets name
     */
    protected $resetSnippets = array('User\\AdminPasswordResetSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $defaultOrgId = null;

        if ($detailed) {
            // Make sure the user is loaded
            $user = $this->getSelectedUser();

            if ($user) {
                switch ($action) {
                    case 'create':
                    case 'show':
                    case 'mail':
                        break;

                    default:
                        $current = $this->loader->getCurrentUser();
                        if (! $user->hasAllowedRole()) {
                            throw new \Gems_Exception($this->_('No access to page'), 403, null, sprintf(
                                    $this->_('As %s user you have no right to access users with the role %s.'),
                                    $current->getRole(),
                                    $user->getRole()
                                    ));
                        }
                        if (! $current->isAllowedOrganization($user->getBaseOrganizationId())) {
                            throw new \Gems_Exception($this->_('No access to page'), 403, null, sprintf(
                                    $this->_('You have no right to access users from the organization %s.'),
                                    $user->getBaseOrganization()->getName()
                                    ));
                        }
                }
                $defaultOrgId = $user->getBaseOrganizationId();
            }
        }

        // \MUtil_Model::$verbose = true;
        $model = $this->loader->getModels()->getStaffModel(! (('deactivate' === $action) || ('reactivate' === $action)));

        $model->applySettings($detailed, $action, $defaultOrgId);

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Staff');
    }

    /**
     * Get the title for the mail
     *
     * @return string
     */
    public function getMailFormTitle()
    {
        $user = $this->getSelectedUser();

        return sprintf($this->_('Send mail to: %s'), $user->getFullName());
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @return array or false
     */
    public function getSearchFilter()
    {
        $filter = parent::getSearchFilter();

        if (! (isset($filter['gsf_id_organization']) && $filter['gsf_id_organization'])) {
            $filter['gsf_id_organization'] = array_keys($this->loader->getCurrentUser()->getAllowedOrganizations());
        }

        return $filter;
    }

    /**
     * Load the user selected by the request - if any
     *
     * @staticvar \Gems_User_User $user
     * @return \Gems_User_User or false when not available
     */
    public function getSelectedUser()
    {
        static $user = null;

        if ($user !== null) {
            return $user;
        }

        $staffId = $this->_getIdParam();
        if ($staffId) {
            $user   = $this->loader->getUserLoader()->getUserByStaffId($staffId);
            $source = $this->menu->getParameterSource();
            $user->applyToMenuSource($source);
        } else {
            $user = false;
        }

        return $user;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('staff member', 'staff members', $count);
    }

    /**
     * mail a staff member
     */
    public function mailAction()
    {
        if ($this->mailSnippets) {
            $params = $this->_processParameters($this->mailParameters);

            $this->addSnippets($this->mailSnippets, $params);
        }
    }

    /**
     * reset a password
     */
    public function resetAction()
    {
        if ($this->resetSnippets) {
            $params = $this->_processParameters($this->resetParameters);

            $this->addSnippets($this->resetSnippets, $params);
        }
    }
}
