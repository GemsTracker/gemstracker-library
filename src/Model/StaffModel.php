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

namespace Gems\Model;

use Gems\Config\ConfigAccessor;
use Gems\Encryption\ValueEncryptor;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Transform\FixedValueTransformer;
use Gems\Model\Type\EncryptedField;
use Gems\Repository\GroupRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\User\Embed\EmbedLoader;
use Gems\User\User;
use Gems\User\UserLoader;
use Gems\User\Validate\PhoneNumberValidator;
use Gems\Util\PhoneNumberFormatter;
use Gems\Util\Translated;
use Gems\Validator\IPRanges;
use Gems\Validator\OneOf;
use Laminas\Db\Sql\Expression;
use MUtil\Validator\NoScript;
use MUtil\Validator\SimpleEmail;
use Zalt\Base\TranslatorInterface;
use Zalt\Filter\RequireOneCapsFilter;
use Zalt\Html\AElement;
use Zalt\Model\Dependency\ValueSwitchDependency;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Validator\Model\ModelUniqueValidator;

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
class StaffModel extends GemsJoinModel
{

    protected User|null $currentUser;
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly UserLoader $userLoader,
        protected readonly Translated $translatedUtil,
        protected readonly ConfigAccessor $configAccessor,
        protected readonly GroupRepository $groupRepository,
        protected readonly EmbedLoader $embedLoader,
        protected readonly ValueEncryptor $valueEncryptor,
    ) {
        parent::__construct('gems__staff', $metaModelLoader, $sqlRunner, $translate, 'staff');

        $this->currentUser = $this->currentUserRepository->getCurrentUser();

        $this->addColumn(
            new Expression("CONCAT(
                    COALESCE(CONCAT(gsf_last_name, ', '), '-, '),
                    COALESCE(CONCAT(gsf_first_name, ' '), ''),
                    COALESCE(gsf_surname_prefix, '')
                    )"),
            'name');
        $this->addColumn(
            new Expression("CASE WHEN gsf_email IS NULL OR gsf_email = '' THEN 0 ELSE 1 END"),
            'can_mail'
        );
        $this->addColumn(
            new Expression("CASE WHEN gsf_active = 1 THEN '' ELSE 'deleted' END"),
            'row_class'
        );

        $allowedGroups = null;
        if ($this->currentUser instanceof User) {
            $allowedGroups = $this->currentUser->getAllowedStaffGroups();
        }
        if ($allowedGroups) {
            $expr = new Expression(sprintf(
                "CASE WHEN gsf_id_primary_group IN (%s) THEN 1 ELSE 0 END",
                implode(", ", array_keys($allowedGroups))
            ));
        } else {
            $expr = new Expression('0');
        }
        $this->addColumn($expr, 'accessible_role');
        $this->metaModel->set('accessible_role', ['default' => 1]);

        $metaModelLoader->setChangeFields($this->metaModel, 'gsf');
    }

    protected function _addLoginSettings(bool $editing): void
    {
        if ($this->currentUser->hasPrivilege('pr.staff.see.all') || (! $editing)) {
            // Select organization
            $options = $this->organizationRepository->getOrganizations();
        } else {
            $options = $this->currentUser->getAllowedOrganizations();
        }
        $this->metaModel->set('gsf_id_organization', [
            'label' => $this->_('Organization'),
            'default' => $this->currentUser->getCurrentOrganizationId(),
            'multiOptions' => $options,
            'required' => true,
        ]);

        $defaultStaffDefinitions = $this->userLoader->getAvailableStaffDefinitions();
        if (1 == count($defaultStaffDefinitions)) {

            $this->metaModel->addTransformer(new FixedValueTransformer([
                'gul_user_class' => key($defaultStaffDefinitions),
            ]));
        } else {
            $this->metaModel->set('gul_user_class', [
                'label' => $this->_('User Definition'),
                'default' => $this->currentUser->getCurrentOrganization()->getDefaultUserClass(),
                'multiOptions' => $defaultStaffDefinitions,
                'order' => $this->metaModel->getOrder('gsf_id_organization') + 1,
                'required' => true,
            ]);
            $this->metaModel->addDependency('StaffUserClassDependency');
        }

        if ($editing) {
            $uniqueFields = ['gsf_login', 'gsf_id_organization'];
            if ($this->configAccessor->isLoginShared()) {
                $uniqueFields = ['gsf_login'];
            }
            $this->metaModel->set('gsf_login', [
                'validators[unique]' => new ModelUniqueValidator($uniqueFields, array('gsf_id_user')),
            ]);
        }
        $this->metaModel->set('gsf_login', [
            'label' => $this->_('Username'),
            'minlength' => 3,
            'required' => true,
            'size' => 15,
        ]);
    }

    public function applyOwnAccountEdit(bool $includeAuth): self
    {
        $noscript = new NoScript();

        $this->metaModel->set('gsf_id_user', ['elementClass' => 'None']);
        $this->metaModel->set('gsf_login', [
            'label' => $this->_('Login Name'),
            'elementClass' => 'Exhibitor',
        ]);
        if ($includeAuth) {
            $this->metaModel->set('gsf_email', [
                'label' => $this->_('E-Mail'),
                'size' => 30,
                'validator' => new SimpleEmail(),
            ]);
        }
        $this->metaModel->set('gsf_first_name', [
            'label' => $this->_('First name'),
            'validator' => $noscript,
        ]);
        $this->metaModel->set('gsf_surname_prefix', [
            'label' => $this->_('Surname prefix'),
            'description' => 'de, van der, \'t, etc...',
            'validator' => $noscript,
        ]);
        $this->metaModel->set('gsf_last_name', [
            'label' => $this->_('Last name'),
            'required' => true,
            'validator' => $noscript,
        ]);
        $this->metaModel->set('gsf_gender', [
            'label' => $this->_('Gender'),
            'multiOptions' => $this->translatedUtil->getGenders(),
            'elementClass' => 'Radio',
            'separator' => '',
        ]);
        if ($includeAuth) {
            $config = $this->configAccessor->getArray();
            $this->metaModel->set('gsf_phone_1', [
                'label' => $this->_('Mobile phone'),
                'validator' => new PhoneNumberValidator($config, $this->translate),
            ]);
            $this->metaModel->setOnSave('gsf_phone_1', new PhoneNumberFormatter($config));
        }

        $this->metaModel->set('gsf_iso_lang', [
            'label' => $this->_('Language'),
            'multiOptions' => $this->configAccessor->getLocales(),
        ]);

        $this->setFilter(array('gsf_id_user' => $this->currentUser->getUserId()));

        return $this;
    }

    /**
     *
     * @param bool $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return self
     */
    public function applySettings(bool $detailed, string $action): self
    {
        $this->metaModel->resetOrder();

        $editing    = in_array($action, ['edit', 'create']);
        $yesNo      = $this->translatedUtil->getYesNo();

        $this->_addLoginSettings($editing);

        if ($detailed) {
            $this->metaModel->set('gsf_id_user', [ModelFormSnippetAbstract::KEEP_VALUE_FOR_SAVE => true]);
            $this->metaModel->set('gul_id_user', [ModelFormSnippetAbstract::KEEP_VALUE_FOR_SAVE => true]);
            $this->metaModel->set('gsf_first_name', [
                'label' => $this->_('First name')
            ]);
            $this->metaModel->set('gsf_surname_prefix', [
                'label' => $this->_('Surname prefix'),
                'description' => $this->_('de, van der, \'t, etc...'),
            ]);
            $this->metaModel->set('gsf_last_name', [
                'label' => $this->_('Last name'),
                'required' => true,
            ]);

            if ($editing) {
                $this->metaModel->set('gsf_first_name', [
                    'filters[ucfirst]' => RequireOneCapsFilter::class,
                ]);
                $this->metaModel->set('gsf_last_name', [
                    'filters[ucfirst]' => RequireOneCapsFilter::class,
                ]);
                $this->metaModel->set('gsf_job_title', [
                    'filters[ucfirst]' => RequireOneCapsFilter::class,
                ]);
            }
        } else {
            $this->metaModel->set('name', [
                'label' => $this->_('Name')
            ]);
        }
        $this->metaModel->setIfExists('gsf_job_title', [
            'label' => $this->_('Function')
        ]);

        $this->metaModel->set('gsf_gender', [
            'label' => $this->_('Gender'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->translatedUtil->getGenders(),
            'separator' => ' ',
        ]);
        $this->metaModel->set('gsf_email', [
            'label' => $this->_('E-Mail'),
            'itemDisplay' => [AElement::class, 'ifmail'],
            'size' => 30,
            'validators[email]' => 'SimpleEmail',
        ]);
        $config = $this->configAccessor->getArray();
        $this->metaModel->set('gsf_phone_1', [
            'label' => $this->_('Mobile phone'),
            'validator' => new PhoneNumberValidator($config, $this->translate),
        ]);
        $this->metaModel->setOnSave('gsf_phone_1', new PhoneNumberFormatter($config));


        $this->metaModel->set('gsf_id_primary_group', [
            'label' => $this->_('Primary group'),
            'default' => $this->currentUser->getDefaultNewStaffGroup(),
            'multiOptions' => $editing ? $this->currentUser->getAllowedStaffGroups() : $this->groupRepository->getStaffGroupOptions()
        ]);

        if ($detailed) {
            $this->metaModel->set('gsf_iso_lang', [
                'label' => $this->_('Language'),
                'default' => $this->configAccessor->getDefaultLocale(),
                'multiOptions' => $this->configAccessor->getLocales(),
            ]);
            $this->metaModel->set('gul_can_login', [
                'elementClass' => 'Checkbox',
            ]);
            $this->metaModel->set('gsf_mail_watcher', [
                'label' => $this->_('Check cron job mail'),
                'description' => $this->_('If checked the user will be mailed when the cron job does not run on time.'),
                'elementClass' => 'Checkbox',
                'multiOptions' => $yesNo,
            ]);
        }

        $this->metaModel->set('gsf_active', [
            'label' => $this->_('Active'),
            'elementClass' => 'None',
            MetaModelInterface::TYPE_ID => new ActivatingYesNoType($yesNo, 'row_class'),
        ]);

        $this->metaModel->set('gul_can_login', [
            'label' => $this->_('Can login'),
            'description' => $this->_('Users can only login when this box is checked.'),
            'default' => 1,
            'multiOptions' => $yesNo,
        ]);

        $this->metaModel->setIfExists('has_authenticator_tfa', [
            'label' => $this->_('Authenticator TFA'),
            'default' => $this->configAccessor->getDefaultTfaRequired(),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);


        $organizations = $this->currentUser->getAllowedOrganizations();
        if (1 == count($organizations)) {
            $this->metaModel->set('gsf_id_organization', [
                'elementClass' => 'Exhibitor',
            ]);
        } else {
            $this->metaModel->set('gsf_id_organization', [
                'options' => $organizations,
            ]);
        }

        return $this;
    }

    /**
     *
     * @param bool $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return self
     */
    public function applySystemUserSettings(bool $detailed, string $action): self
    {
        $this->addLeftTable('gems__systemuser_setup', [
            'gsf_id_user' => 'gsus_id_user',
        ], true);
        $this->metaModel->resetOrder();

        $editing        = ($action == 'edit') || ($action == 'create');
        $yesNo          = $this->translatedUtil->getYesNo();

        $this->_addLoginSettings($editing);

        if ($detailed) {
            $this->metaModel->set('gsf_id_user', [ModelFormSnippetAbstract::KEEP_VALUE_FOR_SAVE => true]);
            $this->metaModel->set('gul_id_user', [ModelFormSnippetAbstract::KEEP_VALUE_FOR_SAVE => true]);
        }
        $this->metaModel->set('gsf_last_name', [
            'label' => $this->_('Description'),
            'description' => $this->_('A description what this user is for.'),
            'required' => true,
        ]);

        $this->metaModel->set('gul_can_login', [
            'label' => $this->_('Can login'),
            'default' => 1,
            'description' => $this->_('System users can only be used when this box is checked.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);
        $this->metaModel->set('gsf_is_embedded', [
            'label' => $this->_('Type'),
            'default' => 1,
            'description' => $this->_('The type of system user.'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->getSystemUserTypes(),
            'separator' => ' ',
        ]);

        $this->metaModel->set('gsf_id_primary_group', [
            'label' => $this->_('Primary group'),
            'default' => $this->currentUser->getDefaultNewStaffGroup(),
            'description' => $this->_('The group of the system user.'),
            'multiOptions' => $editing ? $this->currentUser->getAllowedStaffGroups() : $this->groupRepository->getStaffGroupOptions(),
        ]);

        $this->metaModel->set('gsf_logout_on_survey', [
            'label' => $this->_('Logout on survey'),
            'description' => $this->_('If checked the user will logoff when answering a survey.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
            'validator' => new OneOf(
                $this->metaModel->get('gsf_is_embedded', 'label'),
                'gsf_is_embedded',
                $this->_('Logout on survey.')
            ),
        ]);

        // Set groups for both types of system users
        $activeStaffGroups       = $this->groupRepository->getStaffGroupOptions();
        $allowedRespondentGroups = $this->groupRepository->getRespondentGroupOptions();

        $groups  = ['' => $this->_('(user primary group)')];
        if (('edit' == $action) || ('create' == $action)) {
            $groups[$this->_('Staff')]      = $activeStaffGroups;
            $groups[$this->_('Respondent')] = $allowedRespondentGroups;
        } else {
            $groups += $activeStaffGroups;
            $groups += $allowedRespondentGroups;
        }

        $this->metaModel->set('gsus_deferred_user_group', [
            'label' => $this->_('Used group'),
            'description' => $this->_('The group the deferred user should be changed to'),
            'multiOptions' => $groups,
        ]);

        $this->metaModel->set('gsus_create_user', [
            'label' => $this->_('Can create users'),
            'description' => $this->_('If the asked for user does not exist, can this embedded user create that user? If it cannot the authentication will fail.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);

        $this->metaModel->set('gsus_authentication', [
            'label' => $this->_('Authentication'),
            'default' => 'Gems\\User\\Embed\\Auth\\HourKeySha256',
            'description' => $this->_('The authentication method used to authenticate the embedded user.'),
            'multiOptions' => $this->embedLoader->listAuthenticators(),
        ]);

        $this->metaModel->set('gsus_deferred_user_loader', [
            'label' => $this->_('Deferred user loader'),
            'default' => 'Gems\\User\\Embed\\DeferredUserLoader\\DeferredStaffUser',
            'description' => $this->_('The method used to load an embedded user.'),
            'multiOptions' => $this->embedLoader->listDeferredUserLoaders(),
        ]);

        $this->metaModel->set('gsus_redirect', [
            'label' => $this->_('Redirect method'),
            'default' => 'Gems\\User\\Embed\\Redirect\\RespondentShowPage',
            'description' => $this->_('The page the user is redirected to after successful login.'),
            'multiOptions' => $this->embedLoader->listRedirects(),
        ]);

        $this->metaModel->set('gsus_allowed_ip_ranges', [
            'label' => $this->_('Login allowed from IP Ranges'),
            'description' => $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25'),
            'elementClass' => 'Textarea',
            'itemDisplay' => [$this, 'ipWrap'],
            'rows' => 4,
            'validator' => new IPRanges(),
        ]);

        $this->metaModel->set('gsus_deferred_mvc_layout', [
            'label' => $this->_('Layout'),
            'description' => $this->_('The layout frame used.'),
            'multiOptions' => $this->embedLoader->listLayouts(),
        ]);

        $this->metaModel->set('gsus_deferred_user_layout', [
            'label' => $this->_('Style'),
            'description' => $this->_('The display style used.'),
            'multiOptions' => $this->embedLoader->listStyles(),
        ]);
        $this->metaModel->set('gsus_hide_breadcrumbs', [
            'label' => $this->_('Crumbs display'),
            'default' => '',
            'description' => $this->_('The display style used.'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->embedLoader->listCrumbOptions(),
            'separator' => ' '
        ]);

        $this->metaModel->set('gsf_iso_lang', [
            'label' => $this->_('Language'),
            'default' => $this->configAccessor->getDefaultLocale(),
            'multiOptions' => $this->configAccessor->getLocales(),
        ]);
        $this->metaModel->set('gsus_secret_key', [
            'label' => $this->_('Secret key'),
            'description' => $this->_('Key used for authentication'),
            'elementClass' => 'Textarea',
            'rows' => 3,
        ]);
        $seeKey = ! ($this->currentUser->hasPrivilege('pr.systemuser.seepwd') || $editing);
        $type   = new EncryptedField($this->valueEncryptor, $seeKey);
        $type->apply($this->metaModel, 'gsus_secret_key');

        $this->metaModel->set('gsf_active', [
            'label' => $this->_('Active'),
            'elementClass' => 'None',
            'multiOptions' => $yesNo,
        ]);

        $check  = ['elementClass' => 'Checkbox'];
        $hidden = ['elementClass' => 'Hidden', 'label' => null];
        $select = ['elementClass' => 'Select'];
        $switch = new ValueSwitchDependency(null, $this->translate);
        $switch->setDependsOn('gsf_is_embedded');
        $switch->setSwitches([
            0 => [
                'gsf_logout_on_survey'      => $check,
                'gsus_authentication'       => $hidden,
                'gsus_create_user'          => $hidden,
                'gsus_deferred_user_group'  => $hidden,
                'gsus_deferred_user_loader' => $hidden,
                'gsus_redirect'             => $hidden,
                'gsus_deferred_mvc_layout'  => $hidden,
                'gsus_deferred_user_layout' => $hidden,
                'gsus_hide_breadcrumbs'     => $hidden,
            ],
            1 => [
                'gsf_logout_on_survey'      => $hidden,
                'gsus_authentication'       => $select,
                'gsus_create_user'          => $check,
                'gsus_deferred_user_group'  => $select,
                'gsus_deferred_user_loader' => $select,
                'gsus_redirect'             => $select,
                'gsus_deferred_mvc_layout'  => $select,
                'gsus_deferred_user_layout' => $select,
                'gsus_hide_breadcrumbs'     => ['elementClass' => 'Radio'],
            ],
        ]);
        $this->metaModel->addDependency($switch);

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getSystemUserTypes(): array
    {
        return [
            1 => $this->_('Embedded (EPD) login user'),
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
    public function save(array $newValues, array|null $filter = null, array|null $saveTables = null): array
    {
        //First perform a save
        $savedValues = parent::save($newValues, $filter, $saveTables);

        //Now check if we need to set the password
        if(isset($newValues['fld_password']) && !empty($newValues['fld_password'])) {
            if ($this->getChanged()<1) {
                $this->changed = 1;
            }

            //Now load the userclass and save the password use the $savedValues as for a new
            //user we might not have the id in the $newValues
            $user = $this->userLoader->getUserByStaffId($savedValues['gsf_id_user']);
            if ($user->canSetPassword()) {
                $user->setPassword($newValues['fld_password']);
            }
        }

        return $savedValues;
    }
}
