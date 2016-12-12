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
    public function applySettings($detailed, $action, $defaultOrgId)
    {
        $this->resetOrder();

        $dbLookup   = $this->util->getDbLookup();
        $editing    = ($action == 'edit') || ($action == 'create');
        $translated = $this->util->getTranslated();
        $user       = $this->loader->getCurrentUser();
        $yesNo      = $translated->getYesNo();

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
                'minlength', 4,
                'required', true,
                'size', 15
                );

        if ($user->hasPrivilege('pr.staff.see.all') || (! $editing)) {
            // Select organization
            $options = $dbLookup->getOrganizations();
        } else {
            $options = $user->getAllowedOrganizations();
        }
        $this->set('gsf_id_organization',      'label', $this->_('Organization'),
                'multiOptions', $options,
                'required', true
                );

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
            }
        } else {
            $this->set('name',                 'label', $this->_('Name'));
        }

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
                'multiOptions', $editing ? $user->getAllowedStaffGroups() : $dbLookup->getStaffGroups()
                );


        if ($detailed) {
            // Now try to load the current organization and find out if it has a default user definition
            // otherwise use the defaultStaffDefinition
            $organization = $this->loader->getOrganization(
                    $defaultOrgId ? $defaultOrgId : $user->getCurrentOrganizationId()
                    );
            $this->set('gsf_id_organization', 'default', $organization->getId());

            $this->set('gul_user_class',       'label', $this->_('User Definition'),
                    'default', $organization->get('gor_user_class', $this->defaultStaffDefinition),
                    'multiOptions', $this->loader->getUserLoader()->getAvailableStaffDefinitions()
                    );
            if ($editing) {
                $this->set('gul_user_class', 'order', 1,
                        'required', true);
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
            $this->set('gsf_logout_on_survey', 'label', $this->_('Logout on survey'),
                    'description', $this->_('If checked the user will logoff when answering a survey.'),
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

        $this->setDeleteValues('gsf_active', 0, 'gul_can_login', 0);

        if (! $user->hasPrivilege('pr.staff.edit.all')) {
            $this->set('gsf_id_organization', 'elementClass', 'Exhibitor');
        }

        return $this;
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