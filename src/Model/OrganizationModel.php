<?php

/**
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Model;
use Gems\Model\Transform\HtmlSanitizeTransformer;
use Gems\Screens\ScreenLoader;
use Gems\Tracker\TrackEvents;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use MUtil\Model\Type\ConcatenatedRow;
use Zalt\Html\Html;

/**
 * Contains the organization
 *
 * Handles saving of the user definition config
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OrganizationModel extends \Gems\Model\JoinModel
{
    /**
     * @var array
     */
    protected $_styles;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var Model
     */
    protected $modelLoader;

    /**
     * @var bool Whether or not we are editing
     */
    protected $notEditing = true;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * @var ScreenLoader
     */
    protected $screenLoader;

    /**
     * @var TrackEvents
     */
    protected $trackEvents;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * @var UserLoader
     */
    protected $userLoader;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Constructor
     *
     * @param array|mixed $styles
     */
    public function __construct($styles = array())
    {
        parent::__construct('organization', 'gems__organizations', 'gor');

        $this->_styles = $styles;

        $this->setDeleteValues('gor_active', 0, 'gor_add_respondents', 0);
        $this->addColumn("CASE WHEN gor_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        // \Gems\Model::setChangeFieldsByPrefix($this, 'gor');
    }

    /**
     * Set those settings needed for the browse display
     *
     *
     * @return \Gems\Model\OrganizationModel
     */
    public function applyBrowseSettings()
    {
        $dbLookup    = $this->util->getDbLookup();
        $definitions = $this->userLoader->getAvailableStaffDefinitions();
        $localized   = $this->util->getLocalized();
        $projectName = null;
        $yesNo       = $this->translatedUtil->getYesNo();

        if (isset($this->config['app']['name'])) {
            $projectName = $this->config['app']['name'];
        }

        $this->resetOrder();
        $this->set('gor_name',                  'label', $this->_('Name'), 'tab', $this->_('General'), 'translate', true);
        $this->set('gor_location',              'label', $this->_('Location'), 'translate', true);
        $this->set('gor_task',                  'label', $this->_('Task'),
                'description', sprintf($this->_('Task in %s project'), $projectName), 'translate', true
                );
        $this->set('gor_url',                   'label', $this->_('Company url'),
                   'description', $this->_('The website of the organization, for information purposes.'),
                   'translate', true);

        if (isset($this->config['sites']['allowed'])) {
            $sites = array_column($this->config['sites']['allowed'], 'url');
            $this->set('gor_sites', [
                'label' => $this->_('Available from urls'),
                'description' => $this->_('This organization can be reached from these site url\'s. Leave empty for all sites.'),
                'multiOptions' => $sites,
                'elementClass' => 'MultiCheckbox',
            ]);

            $ct = new ConcatenatedRow('|', $this->_(', '), true);
            $ct->apply($this, 'gor_sites');
        }

        $this->addColumn('gor_id_organization', 'pref_url');
        $this->set('pref_url', 'label', $this->_("Preferred url"), 'elementClass', 'Exhibitor');
        //$this->setOnLoad('pref_url', [$this->util->getSites(), 'getOrganizationPreferredUrl']);

        $this->setIfExists('gor_code',             'label', $this->_('Organization code'),
                'description', $this->_('Optional code name to link the organization to program code.')
                );
        $this->set('gor_provider_id',           'label', $this->_('Healthcare provider id'),
                'description', $this->_('An interorganizational id used for import and export.')
                );

        $this->setIfExists('gor_active',        'label', $this->_('Active'),
                'description', $this->_('Can the organization be used?'),
                'multiOptions', $yesNo
                );

        $this->set('gor_contact_name',          'label', $this->_('Contact name'), 'translate', true);
        $this->set('gor_contact_email',         'label', $this->_('Contact email'));
        $this->set('gor_contact_sms_from',      'label', $this->_('Contact SMS From'));

        // Determine order for details, but do not show in browse
        $this->set('gor_welcome', 'translate', true);
        $this->set('gor_signature', 'translate', true);
        $this->set('gor_create_account_template');
        $this->set('gor_reset_pass_template');
        $this->set('gor_reset_tfa_template');


        $this->set('gor_has_login',             'label', $this->_('Login'),
                'description', $this->_('Can people login for this organization?'),
                'multiOptions', $yesNo
                );
        $this->set('gor_add_respondents',       'label', $this->_('Accepting'),
                'description', $this->_('Can new respondents be added to the organization?'),
                'multiOptions', $yesNo
                );
        $this->set('gor_has_respondents',       'label', $this->_('Respondents'),
                'description', $this->_('Does the organization have respondents?'),
                'multiOptions', $yesNo
                );
        $this->set('gor_respondent_group',      'label', $this->_('Respondent group'),
                'description', $this->_('Allows respondents to login.'),
                'multiOptions', $dbLookup->getAllowedRespondentGroups()
                );
        $this->set('gor_accessible_by',            'label', $this->_('Accessible by'),
                'description', $this->_('Checked organizations see this organizations respondents.'),
                'multiOptions', $dbLookup->getOrganizations()
                );
        $tp = new \MUtil\Model\Type\ConcatenatedRow(':', ', ');
        $tp->apply($this, 'gor_accessible_by');

        $this->setIfExists('gor_allowed_ip_ranges');

        if ($definitions) {
            reset($definitions);
            $this->setIfExists('gor_user_class',    'label', $this->_('User Definition'),
                    'default', key($definitions),
                    'multiOptions', $definitions
                    );
            if (1 == count($definitions)) {
                $this->setIfExists('gor_user_class',
                        'elementClass', 'None'
                        );
            }
        }

        $groupLevel   = [
            '' => $this->_('Defer to user group setting'),
            ];

        $this->setIfExists('gor_respondent_edit', 'label', $this->_('Respondent edit screen'),
                'multiOptions', $groupLevel + $this->screenLoader->listRespondentEditScreens()
                );
        $this->setIfExists('gor_respondent_show', 'label', $this->_('Respondent show screen'),
                'multiOptions', $groupLevel + $this->screenLoader->listRespondentShowScreens()
                );
        $this->setIfExists('gor_respondent_subscribe', 'label', $this->_('Subscribe screen'),
                'multiOptions', $this->screenLoader->listSubscribeScreens()
                );
        $this->setIfExists('gor_respondent_unsubscribe', 'label', $this->_('Unsubscribe screen'),
                'multiOptions', $this->screenLoader->listUnsubscribeScreens(),
                );
        $this->setIfExists('gor_token_ask', 'label', $this->_('Token ask screen'),
                'multiOptions', $this->screenLoader->listTokenAskScreens()
                );

        $this->setIfExists('gor_resp_change_event', 'label', $this->_('Respondent change event'),
                'multiOptions', $this->trackEvents->listRespondentChangedEvents()
                );
        $this->setIfExists('gor_iso_lang',      'label', $this->_('Language'),
                'multiOptions', $localized->getLanguages()
                );
        if ($this->_styles) {
            $this->setIfExists('gor_style',     'label', $this->_('Style'), 'multiOptions', $this->_styles);
        }

        if ($this->notEditing && $this->project->translateDatabaseFields()) {
            $this->modelLoader->addDatabaseTranslations($this);
        }

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\OrganizationModel
     */
    public function applyDetailSettings()
    {
        $commUtil = $this->util->getCommTemplateUtil();

        $staffTemplates = $commUtil->getCommTemplatesForTarget('staffPassword', null, true);

        $this->applyBrowseSettings();

        $this->set('gor_welcome',                   'label', $this->_('Greeting'),
                'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        $this->set('gor_signature',                 'label', $this->_('Signature'),
                'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        $this->set('gor_create_account_template',   'label', $this->_('Create Account template'),
                'default', $commUtil->getCommTemplateForCode('accountCreate', 'staffPassword'),
                'multiOptions', $staffTemplates);
        $this->set('gor_reset_pass_template',       'label', $this->_('Reset Password template'),
                'default', $commUtil->getCommTemplateForCode('passwordReset', 'staffPassword'),
                'multiOptions', $staffTemplates);
        $this->set('gor_reset_tfa_template',       'label', $this->_('Reset TFA template'),
            'default', $commUtil->getCommTemplateForCode('tfaReset', 'staffPassword'),
            'multiOptions', $staffTemplates);

        $this->setIfExists('gor_allowed_ip_ranges', 'label', $this->_('Allowed IP Ranges'),
            'description', $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25')
            );

        if ($this->notEditing && $this->project->translateDatabaseFields()) {
            $this->modelLoader->addDatabaseTranslations($this);
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems\Model\OrganizationModel
     */
    public function applyEditSettings()
    {
        $this->notEditing = false;

        $this->applyDetailSettings();
        $this->resetOrder();

        $yesNo = $this->translatedUtil->getYesNo();

        // GENERAL TAB
        /*$html = Html::create()->h4($this->_('General'));
        $this->set('general', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);*/
        $this->set('gor_name',
                'size', 25,
                'validator', $this->createUniqueValidator('gor_name')
                );
        $this->set('gor_location',
                'size', 50,
                'maxlength', 255
                );
        $this->set('gor_task',
                'size', 25);
        $this->set('gor_url', [
            'size' => 50,
            'validators[url]' => 'ExistingUrl',
            ]);
        $this->setIfExists('gor_code',
                'size', 10
                );
        $this->set('gor_provider_id');
        $this->setIfExists('gor_active',
                'elementClass', 'Checkbox'
                );

        // EMAIL TAB
        $html = Html::create()->h4($this->_('Email') . ' & ' . $this->_('Token'));
        $this->set('emailAndToken', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);
        $this->set('gor_contact_name',
                'order', $this->getOrder('gor_active') + 1000,
                'size', 25
                );
        $this->set('gor_contact_email',
                'size', 50,
                'validator', 'SimpleEmail'
                );
        $this->set('gor_contact_sms_from',
                'size', 50,
                'maxlength', 11,
                'description', $this->_('The from field for an sms.')
        );
        $this->set('gor_mail_watcher', 'label', $this->_('Check cron job mail'),
                    'description', $this->_('If checked the organization contact will be mailed when the cron job does not run on time.'),
                    'elementClass', 'Checkbox',
                    'multiOptions', $yesNo
                    );
        $this->set('gor_welcome',
                'elementClass', 'Textarea',
                'rows', 5
                );
        $this->set('gor_signature',
                'elementClass', 'Textarea',
                'rows', 5
                );
        $this->set('gor_create_account_template');
        $this->set('gor_reset_pass_template');
        $this->set('gor_reset_tfa_template');

        // ACCESS TAB
        $html = Html::create()->h4($this->_('Access'));
        $this->set('access', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);
        $this->set('gor_has_login',
                'order', $this->getOrder('gor_reset_pass_template') + 1000,
                'elementClass', 'CheckBox'
                );
        $this->set('gor_add_respondents',
                'elementClass', 'CheckBox'
                );
        $this->set('gor_has_respondents',
                'elementClass', 'Exhibitor'
                );
        $this->set('gor_respondent_group');
        $this->set('gor_accessible_by',
                'elementClass', 'MultiCheckbox'
                );
        $this->set('allowed',
                'label', $this->_('Can access'),
                'elementClass', 'Html'
                );

        $this->setIfExists('gor_allowed_ip_ranges',
            'elementClass', 'Textarea',
            'rows', 4,
            'validator', new \Gems\Validator\IPRanges()
            );
        $this->setIfExists('gor_user_class');

        $definitions = $this->get('gor_user_class', 'multiOptions');
        if ($definitions && (count($definitions) > 1)) {
            reset($definitions);
            // MD: Removed onchange because it does not play nice with the processAfterLoad and save methods in this class
            //     @@TODO: See if we can enable it when these methods are changed into a dependency
            $this->setIfExists('gor_user_class', [
                'autoSubmit' => true,
                'default' => key($definitions),
                'required' => true,
            ]);
        }

        // INTERFACE TAB
        $html = Html::create()->h4($this->_('Interface'));
        $this->set('interface', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);
        $this->setIfExists('gor_respondent_edit',
                'default', '',
                'elementClass', 'Radio'
                );
        $this->setIfExists('gor_respondent_show',
                'default', '',
                'elementClass', 'Radio'
                );
        $this->setIfExists('gor_respondent_subscribe',
                'default', '',
                'elementClass', 'Radio'
                );
        $this->setIfExists('gor_respondent_unsubscribe',
                'default', '',
                'elementClass', 'Radio'
                );

        $this->setIfExists('gor_token_ask',
                'default', 'Gems\\Screens\\Token\\Ask\\ProjectDefaultAsk',
                'elementClass', 'Radio'
                );

        $this->setIfExists('gor_resp_change_event',
                'order', $this->getOrder('gor_user_class') + 1000
                );
        $this->setIfExists('gor_iso_lang',
                'order', $this->getOrder('gor_user_class') + 1010,
                'default', $this->project->getLocaleDefault()
                );

        if ($this->_styles) {
            $this->setIfExists('gor_style');
        }

        $this->addTransformer(new HtmlSanitizeTransformer([
            'gor_welcome',
            'gor_signature',
        ]));

        if ($this->project->translateDatabaseFields()) {
            $this->modelLoader->addDatabaseTranslationEditFields($this);
        }

        return $this;
    }

    /**
     * Helper function that procesess the raw data after a load.
     *
     * @see \MUtil\Model\SelectModelPaginator
     *
     * @param mixed $data Nested array or \Traversable containing rows or iterator
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array or \Traversable Nested
     */
    public function processAfterLoad($data, $new = false, $isPostData = false): mixed
    {
        $data = parent::processAfterLoad($data, $new, $isPostData);

        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }
        foreach ($data as &$row) {
            if (isset($row['gor_user_class']) && !empty($row['gor_user_class'])) {
                $definition = $this->userLoader->getUserDefinition($row['gor_user_class']);

                if ($definition instanceof \Gems\User\UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                    $definition->addConfigFields($this);
                    $row = $row + $definition->loadConfig($row);
                }
            }

        }
        return $data;
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
    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        //First perform a save
        $savedValues = parent::save($newValues, $filter, $saveTables);

        //Now check if we need to save config values
        if (isset($newValues['gor_user_class']) && !empty($newValues['gor_user_class'])) {
            $definition = $this->loader->getUserLoader()->getUserDefinition($newValues['gor_user_class']);

            if ($definition instanceof \Gems\User\UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                $savedValues = $definition->saveConfig($savedValues, $newValues);

                if ($definition->getConfigChanged()>0 && $this->getChanged()<1) {
                    $this->setChanged(1);
                }
            }
        }

        return $savedValues;
    }
}
