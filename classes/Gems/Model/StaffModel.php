<?php

/**
 * The staff model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use MUtil\Model\Dependency\ValueSwitchDependency;

/**
 * Contains the staffModel
 *
 * Handles saving of the password to the right userclass
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Model_StaffModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Gems_User_Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * One of the user classes available to the user loader
     *
     * @var string
     */
    protected $defaultStaffDefinition = \Gems_User_UserLoader::USER_STAFF;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Create a model that joins two or more tables
     *
     * @param string $name An alternative name for the model
     */
    public function __construct($name = 'staff')
    {
        parent::__construct('staff', 'gems__staff', 'gsf', true);

        $this->addColumn(
                new \Zend_Db_Expr("CONCAT(
                    COALESCE(CONCAT(gsf_last_name, ', '), '-, '),
                    COALESCE(CONCAT(gsf_first_name, ' '), ''),
                    COALESCE(gsf_surname_prefix, '')
                    )"),
                'name');
        $this->addColumn(
                new \Zend_Db_Expr("CASE WHEN gsf_email IS NULL OR gsf_email = '' THEN 0 ELSE 1 END"),
                'can_mail'
                );
        $this->addColumn(
                new \Zend_Db_Expr("CASE WHEN gsf_active = 1 THEN '' ELSE 'deleted' END"),
                'row_class'
                );
    }

    /**
     *
     * @param boolean $editing
     */
    protected function _addLoginSettings($editing)
    {
        if ($this->currentUser->hasPrivilege('pr.staff.see.all') || (! $editing)) {
            // Select organization
            $options = $this->util->getDbLookup()->getOrganizations();
        } else {
            $options = $this->currentUser->getAllowedOrganizations();
        }
        $this->set('gsf_id_organization',      'label', $this->_('Organization'),
                'default', $this->currentOrganization->getId(),
                'multiOptions', $options,
                'required', true
                );

        $defaultStaffDefinitions = $this->loader->getUserLoader()->getAvailableStaffDefinitions();
        if (1 == count($defaultStaffDefinitions)) {
            reset($defaultStaffDefinitions);
            $this->set('gul_user_class',
                    'default', key($defaultStaffDefinitions),
                    'elementClass', 'Hidden',
                    'multiOptions', $defaultStaffDefinitions,
                    'required', false
                    );
        } else {
            $this->set('gul_user_class',       'label', $this->_('User Definition'),
                    'default', $this->currentOrganization->getDefaultUserClass(),
                    'multiOptions', $defaultStaffDefinitions,
                    'order', $this->getOrder('gsf_id_organization') + 1,
                    'required', true
                    );
            $this->addDependency('StaffUserClassDependency');
        }

        if ($editing) {
            if ($this->project->isLoginShared()) {
                $this->set('gsf_login', 'validator', $this->createUniqueValidator('gsf_login', array('gsf_id_user')));
            } else {
                // per organization
                $this->set(
                        'gsf_login',
                        'validator',
                        $this->createUniqueValidator(array('gsf_login', 'gsf_id_organization'), array('gsf_id_user'))
                        );
            }
        }
        $this->set('gsf_login',                'label', $this->_('Username'),
                'minlength', 3,
                'required', true,
                'size', 15
                );
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $allowedGroups = $this->loader->getCurrentUser()->getAllowedStaffGroups();
        if ($allowedGroups) {
            $expr = new \Zend_Db_Expr(sprintf(
                    "CASE WHEN gsf_id_primary_group IN (%s) THEN 1 ELSE 0 END",
                    implode(", ", array_keys($allowedGroups))
                    ));
        } else {
            $expr = new \Zend_Db_Expr('0');
        }
        $this->addColumn($expr, 'accessible_role');
        $this->set('accessible_role', 'default', 1);
    }

    /**
     *
     * @return \Gems_Model_StaffModel
     */
    public function applyOwnAccountEdit()
    {
        $noscript = new \MUtil_Validate_NoScript();

        $this->set('gsf_id_user',        'elementClass', 'None');
        $this->set('gsf_login',          'label', $this->_('Login Name'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsf_email',          'label', $this->_('E-Mail'),
                'size', 30,
                'validator', new \MUtil_Validate_SimpleEmail()
                );
        $this->set('gsf_first_name',     'label', $this->_('First name'), 'validator', $noscript);
        $this->set('gsf_surname_prefix', 'label', $this->_('Surname prefix'),
                'description', 'de, van der, \'t, etc...',
                'validator', $noscript
                );
        $this->set('gsf_last_name',      'label', $this->_('Last name'),
                'required', true,
                'validator', $noscript
                );
        $this->set('gsf_gender',         'label', $this->_('Gender'),
                'multiOptions', $this->util->getTranslated()->getGenders(),
                'elementClass', 'Radio',
                'separator', ''
                );
        $this->set('gsf_iso_lang',       'label', $this->_('Language'),
                'multiOptions', $this->util->getLocalized()->getLanguages()
                );

        $this->setFilter(array('gsf_id_user' => $this->loader->getCurrentUser()->getUserId()));

        return $this;
    }

    /**
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @param int $defaultOrgId The default organization id or null if current organization
     * @return \Gems_Model_StaffModel
     */
    public function applySettings($detailed, $action)
    {
        $this->resetOrder();

        $dbLookup   = $this->util->getDbLookup();
        $editing    = ($action == 'edit') || ($action == 'create');
        $translated = $this->util->getTranslated();
        $yesNo      = $translated->getYesNo();

        $this->_addLoginSettings($editing);

        if ($detailed) {
            $this->set('gsf_first_name',       'label', $this->_('First name'));
            $this->set('gsf_surname_prefix',   'label', $this->_('Surname prefix'),
                    'description', $this->_('de, van der, \'t, etc...')
                    );
            $this->set('gsf_last_name',        'label', $this->_('Last name'),
                    'required', true);

            if ($editing) {
                $ucfirst = new \Zend_Filter_Callback('ucfirst');
                $this->set('gsf_first_name',   'filters[ucfirst]', $ucfirst);
                $this->set('gsf_last_name',    'filters[ucfirst]', $ucfirst);
                $this->set('gsf_job_title',    'filters[ucfirst]', $ucfirst);
            }
        } else {
            $this->set('name',                 'label', $this->_('Name'));
        }
        $this->setIfExists('gsf_job_title', 'label', $this->_('Function'));


        $this->set('gsf_gender',               'label', $this->_('Gender'),
                'elementClass', 'Radio',
                'multiOptions', $translated->getGenders(),
                'separator', ' '
                );
        $this->set('gsf_email',                'label', $this->_('E-Mail'),
                'itemDisplay', array('MUtil_Html_AElement', 'ifmail'),
                'size', 30,
                'validators[email]', 'SimpleEmail'
                );


        $this->set('gsf_id_primary_group',     'label', $this->_('Primary function'),
                'default', $this->currentUser->getDefaultNewStaffGroup(),
                'multiOptions', $editing ? $this->currentUser->getAllowedStaffGroups() : $dbLookup->getStaffGroups()
                );


        if ($detailed) {
            $this->set('gsf_id_organization', 'default', $this->currentOrganization->getId());

            $defaultStaffDefinitions = $this->loader->getUserLoader()->getAvailableStaffDefinitions();
            if (1 == count($defaultStaffDefinitions)) {
                reset($defaultStaffDefinitions);
                $this->set('gul_user_class',
                        'default', key($defaultStaffDefinitions),
                        'elementClass', 'Hidden',
                        'multiOptions', $defaultStaffDefinitions,
                        'required', false
                        );
            } else {
                $this->set('gul_user_class',       'label', $this->_('User Definition'),
                        'default', $this->currentOrganization->getDefaultUserClass(),
                        'multiOptions', $defaultStaffDefinitions,
                        'order', $this->getOrder('gsf_id_organization') + 1,
                        'required', true
                        );
                $this->addDependency('StaffUserClassDependency');
            }
            $this->set('gsf_iso_lang',         'label', $this->_('Language'),
                    'default', $this->project->locale['default'],
                    'multiOptions', $this->util->getLocalized()->getLanguages()
                    );
            $this->set('gul_can_login',        'label', $this->_('Can login'),
                    'default', 1,
                    'description', $this->_('Users can only login when this box is checked.'),
                    'elementClass', 'Checkbox',
                    'multiOptions', $yesNo
                    );
            $this->set('gsf_mail_watcher', 'label', $this->_('Check cron job mail'),
                    'description', $this->_('If checked the user will be mailed when the cron job does not run on time.'),
                    'elementClass', 'Checkbox',
                    'multiOptions', $yesNo
                    );
        }

        $this->set('gsf_active', 'label', $this->_('Active'),
                'elementClass', 'None',
                'multiOptions', $yesNo
                );

        $factorOptions = [
            2 => $this->_('Enabled'),
            1 => $this->_('Not set'),
            0 => $this->_('Disabled'),
            -1 => $this->_('Not possible'),
        ];
        $this->setIfExists('has_2factor', 'label', $this->_('Two factor'),
                'elementClass', 'Exhibitor',
                'multiOptions', $factorOptions
                );
        if ($detailed) {
            $this->setIfExists('gul_enable_2factor', 'label', $this->_('Two factor enabled'),
                    'description', $this->_('You can only enable/disable two factor authentication, not install a key.'),
                    'elementClass', 'Checkbox',
                    'multiOptions', $yesNo
                    );
        }

        $this->setDeleteValues('gsf_active', 0, 'gul_can_login', 0);

        if (! $this->currentUser->hasPrivilege('pr.staff.edit.all')) {
            $this->set('gsf_id_organization', 'elementClass', 'Exhibitor');
        }

        return $this;
    }

    /**
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \Gems_Model_StaffModel
     */
    public function applySystemUserSettings($detailed, $action)
    {
        $this->addLeftTable('gems__systemuser_setup', ['gsf_id_user' => 'gsus_id_user'], 'gsus');
        $this->resetOrder();

        $dbLookup       = $this->util->getDbLookup();
        $editing        = ($action == 'edit') || ($action == 'create');
        $embeddedLoader = $this->loader->getEmbedLoader();
        $translated     = $this->util->getTranslated();
        $yesNo          = $translated->getYesNo();

        $this->_addLoginSettings($editing);

        $this->set('gsf_last_name',        'label', $this->_('Description'),
                'description', $this->_('A description what this user is for.'),
                'required', true);

        $this->set('gul_can_login',        'label', $this->_('Can login'),
                'default', 1,
                'description', $this->_('System users can only be used when this box is checked.'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $this->set('gsf_is_embedded',      'label', $this->_('Type'),
                'default', 1,
                'description', $this->_('The type of system user.'),
                'elementClass', 'Radio',
                'multiOptions', $this->getSystemUserTypes(),
                'separator', ' '
                );

        $this->set('gsf_id_primary_group', 'label', $this->_('Primary function'),
                'default', $this->currentUser->getDefaultNewStaffGroup(),
                'description', $this->_('The group of the system user.'),
                'multiOptions', $editing ? $this->currentUser->getAllowedStaffGroups() : $dbLookup->getStaffGroups()
                );

        $this->set('gsf_logout_on_survey', 'label', $this->_('Logout on survey'),
                'description', $this->_('If checked the user will logoff when answering a survey.'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo,
                'validator', new \Gems_Validate_OneOf(
                        $this->get('gsf_is_embedded', 'label'),
                        'gsf_is_embedded',
                        $this->_('Logout on survey.')
                        )
                );

        // Set groups for both types of system users
        $dbLookup = $this->util->getDbLookup();
        $allowedRespondentGroups = $dbLookup->getAllowedRespondentGroups();
        unset($allowedRespondentGroups['']);
        $groups = [
            '' => $this->_('(user primary group)'),
            $this->_('Staff') => $dbLookup->getAllowedStaffGroups(),
            $this->_('Respondent') => $allowedRespondentGroups,
        ];

        $this->set('gsus_deferred_user_group',
                'label', $this->_('Used group'),
                'description', $this->_('The group the deferred user should be changed to'),
                'multiOptions', $groups
                );

        $this->set('gsus_create_user',
                'label', $this->_('Can create users'),
                'description', $this->_('If the asked for user does not exits, can this embedded user create that user? If it cannot the authentication will fail.'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );

        $this->set('gsus_authentication',
                'label', $this->_('Authentication'),
                'default', 'Gems\\User\\Embed\\Auth\\HourKeySha256',
                'description', $this->_('The authentication method used to authenticate the embedded user. Possibly adding characteristics of the deferred user as well.'),
                'multiOptions', $embeddedLoader->listAuthenticators()
                );

        $this->set('gsus_deferred_user_loader',
                'label', $this->_('Deferred user loader'),
                'default', 'Gems\\User\\Embed\\DeferredUserLoader\\StaffUser',
                'description', $this->_('The method a deferred user should be loaded.'),
                'multiOptions', $embeddedLoader->listDeferredUserLoaders()
                );

        $this->set('gsus_redirect',
                'label', $this->_('Redirect method'),
                'default', 'Gems\\User\\Embed\\Redirect\\RespondentShowPage',
                'description', $this->_('The method the user is redirected after successful login.'),
                'multiOptions', $embeddedLoader->listRedirects()
                );

        $this->set('gsus_deferred_user_layout',
                'label', $this->_('Layout'),
                'description', $this->_('The layout the user should be changed to'),
                'multiOptions', $embeddedLoader->listLayouts()
                );

        $this->set('gsf_iso_lang',         'label', $this->_('Language'),
                'default', $this->project->locale['default'],
                'multiOptions', $this->util->getLocalized()->getLanguages()
                );
        $this->set('gsus_secret_key',      'label', $this->_('Secret key'),
                'description', $this->_('Key used for authentication'),
                'elementClass', 'Textarea',
                'rows', 3
                );
        $seeKey = ! ($this->currentUser->hasPrivilege('pr.systemuser.seepwd') || $editing);
        $type   = new \Gems_Model_Type_EncryptedField($this->project, $seeKey);
        $type->apply($this, 'gsus_secret_key');

        $this->set('gsf_active', 'label', $this->_('Active'),
                'elementClass', 'None',
                'multiOptions', $yesNo
                );

        $check  = ['elementClass' => 'Checkbox'];
        $hidden = ['elementClass' => 'Hidden', 'label' => null];
        $select = ['elementClass' => 'Select'];
        $switch = new ValueSwitchDependency();
        $switch->setDependsOn('gsf_is_embedded');
        $switch->setSwitches([
            0 => [
                'gsf_logout_on_survey'      => $check,
                'gsus_authentication'       => $hidden,
                'gsus_create_user'          => $hidden,
                'gsus_deferred_user_group'  => $hidden,
                'gsus_deferred_user_loader' => $hidden,
                'gsus_redirect'             => $hidden,
                'gsus_deferred_user_layout' => $hidden,
                ],
            1 => [
                'gsf_logout_on_survey'      => $hidden,
                'gsus_authentication'       => $select,
                'gsus_create_user'          => $check,
                'gsus_deferred_user_group'  => $select,
                'gsus_deferred_user_loader' => $select,
                'gsus_redirect'             => $select,
                'gsus_deferred_user_layout' => $select,
                ],
            ]);
        $this->addDependency($switch);

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getSystemUserTypes()
    {
        return [
            1 => $this->_('Embbedded (EPD) login user'),
            0 => $this->_('Guest - for answering surveys at the hospital'),
            ];
    }

    /**
     * Save a single model item.
     *
     * Makes sure the password is saved too using the userclass
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @param array $saveTables Optional array containing the table names to save,
     * otherwise the tables set to save at model level will be saved.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        //First perform a save
        $savedValues = parent::save($newValues, $filter, $saveTables);

        //Now check if we need to set the password
        if(isset($newValues['fld_password']) && !empty($newValues['fld_password'])) {
            if ($this->getChanged()<1) {
                $this->setChanged(1);
            }

            //Now load the userclass and save the password use the $savedValues as for a new
            //user we might not have the id in the $newValues
            $user = $this->loader->getUserLoader()->getUserByStaffId($savedValues['gsf_id_user']);
            if ($user->canSetPassword()) {
                $user->setPassword($newValues['fld_password']);
            }
        }

        return $savedValues;
    }
}