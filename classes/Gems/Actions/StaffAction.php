<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class StaffAction extends \Gems\Controller\ModelSnippetActionAbstract
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
        'extraFilter' => [
            'gsf_is_embedded' => 0,
            'gsf_logout_on_survey' => 0,
            ],
        ];

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
        'routeAction' => 'reset',
        ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Staff\\StaffCreateEditSnippet';

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

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
    protected $mailSnippets = array('Mail\\MailFormSnippet');

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
        'askOld'           => false,   // Do not ask for the old password
        'forceRules'       => false,   // If user logs in using password that does not obey the rules, he is forced to change it
        'menuShowChildren' => true,
        'menuShowSiblings' => true,
        'routeAction'      => 'show',
        'user'             => 'getSelectedUser',
        );

    /**
     * Snippets for reset
     *
     * @var mixed String or array of snippets name
     */
    protected $resetSnippets = array('User\\AdminPasswordResetSnippet');

    /**
     * The parameters used for the mail action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $switchParameters = array(
        'switch' => true,
        );

    /**
     * Snippets for mail
     *
     * @var mixed String or array of snippets name
     */
    protected $switchSnippets = array('Staff\\SystemUserCreateEditSnippet');

    /**
     * True for staff model, otherwise system user model
     *
     * @var boolean
     */
    protected $useStaffModel = true;

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $defaultOrgId = null;

        if ($detailed) {
            // Make sure the user is loaded
            $user = $this->getSelectedUser();

            if ($user) {
                if (! ($this->currentUser->hasPrivilege('pr.staff.see.all') ||
                        $this->currentUser->isAllowedOrganization($user->getBaseOrganizationId()))) {
                    throw new \Gems\Exception($this->_('No access to page'), 403, null, sprintf(
                            $this->_('You have no right to access users from the organization %s.'),
                            $user->getBaseOrganization()->getName()
                            ));
                }

                switch ($action) {
                    case 'create':
                    case 'show':
                    case 'mail':
                        break;

                    default:
                        if (! $user->inAllowedGroup()) {
                            throw new \Gems\Exception($this->_('No access to page'), 403, null, sprintf(
                                    $this->_('In the %s group you have no right to change users in the %s group.'),
                                    $this->currentUser->getGroup()->getName(),
                                    $user->getGroup()->getName()
                                    ));
                        }
                }
            }
        }

        // \MUtil\Model::$verbose = true;
        $model = $this->loader->getModels()->getStaffModel(! (('deactivate' === $action) || ('reactivate' === $action)));

        if ($this->useStaffModel) {
            $model->applySettings($detailed, $action);
        } else {
            $model->applySystemUserSettings($detailed, $action);
        }

        return $model;
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
            return sprintf($this->_('Deactivate staff member %s'), $user->getLoginName());
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
     * Helper function to get the title for the reactivate action.
     *
     * @return $string
     */
    public function getReactivateTitle()
    {
        $user = $this->getSelectedUser();

        if ($user) {
            return sprintf($this->_('Reactivate staff member %s'), $user->getLoginName());
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

        if (! (isset($filter['gsf_id_organization']) && $filter['gsf_id_organization'])) {
            $filter['gsf_id_organization'] = array_keys($this->currentUser->getAllowedOrganizations());
        }

        return $filter;
    }

    /**
     * Load the user selected by the request - if any
     *
     * @staticvar \Gems\User\User $user
     * @return \Gems\User\User or false when not available
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
     * Helper function to get the title for the show action.
     *
     * @return $string
     */
    public function getShowTitle()
    {
        $user = $this->getSelectedUser();

        if ($user) {
            return sprintf($this->_('Show staff member %s'), $user->getLoginName());
        }

        return parent::getShowTitle();
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

    /**
     * reset two factor authentication
     */
    public function reset2faAction()
    {
        $user = $this->getSelectedUser();
        $user->clearTwoFactorKey();
        $this->addMessage(
                sprintf($this->_('Two factor key cleared for user %s'),
                        $user->getLoginName()),
                'success');
        $router = \Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $router->gotoRoute(['controller'=>'staff', 'action'=>'show'], null, false);
    }

    public function switchUserAction()
    {
        $this->useStaffModel = ! $this->useStaffModel;
        if ($this->switchSnippets) {
            $params = $this->_processParameters($this->switchParameters);

            $this->addSnippets($this->switchSnippets, $params);
        }
    }
}
