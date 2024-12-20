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

use Gems\Html;
use Gems\Locale\Locale;
use Gems\Model\Transform\HtmlSanitizeTransformer;
use Gems\Model\Transform\OrganizationConfigurableUserDefinitionTransformer;
use Gems\Repository\CommTemplateRepository;
use Gems\Repository\GroupRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Screens\ScreenLoader;
use Gems\Tracker\TrackEvents;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use Gems\Validator\IPRanges;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\Validator\Model\ModelUniqueValidator;

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
class OrganizationModel extends GemsJoinModel
{
    public CONST URL_SEPARATOR = '|';

   protected string $projectName;

    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly CommTemplateRepository $commTemplateRepository,
        protected readonly GroupRepository $groupRepository,
        protected readonly Locale $locale,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ScreenLoader $screenLoader,
        protected readonly TrackEvents $trackEvents,
        protected readonly Translated $translatedUtil,
        protected readonly UserLoader $userLoader,

        array $config,
        protected readonly array $styles = [],
    ) {
        parent::__construct('gems__organizations', $metaModelLoader, $sqlRunner, $translate, 'organization');

        $this->metaModelLoader->setChangeFields($this->metaModel, 'gor');

        $this->addColumn("CASE WHEN gor_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        $yesNo      = $this->translatedUtil->getYesNo();

        $this->metaModel->set('gor_active', [
            'label' => $this->_('Active'),
            'elementClass' => 'None',
            'type' => new ActivatingYesNoType($yesNo, 'row_class'),
        ]);

        $this->metaModel->set('gor_add_respondents', [
            'label' => $this->_('Active'),
            'elementClass' => 'None',
            'type' => new ActivatingYesNoType($yesNo, 'row_class'),
        ]);

        $this->projectName = $config['app']['name'] ?? '';
    }

    /**
     * Set those settings needed for the browse display
     *
     *
     * @return static
     */
    public function applyBrowseSettings($setTranslations = true): static
    {
        $definitions = $this->userLoader->getAvailableStaffDefinitions();
        $yesNo       = $this->translatedUtil->getYesNo();
        $empty       = $this->translatedUtil->getEmptyDropdownArray();

        $this->metaModel->resetOrder();
        $this->metaModel->set('gor_name', [
            'label' => $this->_('Name'),
            'tab' => $this->_('General'),
            'translate' => true
        ]);
        $this->metaModel->set('gor_location', [
            'label' => $this->_('Location'),
            'translate' =>  true
        ]);
        $this->metaModel->set('gor_task', [
            'label' => $this->_('Task'),
            'description' => sprintf($this->_('Task in %s project'), $this->projectName),
            'translate' => true,
        ]);
        $this->metaModel->set('gor_url', [
            'label' => $this->_('Company url'),
            'description' => $this->_('The website of the organization, for information purposes.'),
            'translate' => true
        ]);

        $this->metaModel->set('gor_sites', [
            'label' => $this->_('Available from urls'),
            'description' => $this->_('This organization can be reached from these site url\'s. Leave empty for all sites.'),
            'multiOptions' => $this->userLoader->getSiteUrls(),
            'elementClass' => 'MultiCheckbox',
            'type' => new ConcatenatedType(static::URL_SEPARATOR, $this->_(', '), true),
        ]);

        $this->metaModel->setIfExists('gor_code', [
            'label' => $this->_('Organization code'),
            'description' => $this->_('Optional code name to link the organization to program code.'),
        ]);
        $this->metaModel->set('gor_provider_id', [
            'label' => $this->_('Healthcare provider id'),
            'description' => $this->_('An interorganizational id used for import and export.'),
        ]);

        $this->metaModel->setIfExists('gor_active', [
            'label' => $this->_('Active'),
            'description' => $this->_('Can the organization be used?'),
            'multiOptions' => $yesNo,
        ]);

        $this->metaModel->set('gor_contact_name', [
            'label' => $this->_('Contact name'),
            'translate' => true
        ]);
        $this->metaModel->set('gor_contact_email', [
            'label' => $this->_('Contact email')
        ]);
        $this->metaModel->set('gor_contact_sms_from', [
            'label' => $this->_('Contact SMS From')
        ]);

        // Determine order for details, but do not show in browse
        $this->metaModel->set('gor_welcome', [
            'translate' => true
        ]);
        $this->metaModel->set('gor_signature', [
            'translate' => true
        ]);
        $this->metaModel->set('gor_create_account_template');
        $this->metaModel->set('gor_reset_pass_template');
        $this->metaModel->set('gor_reset_tfa_template');


        $this->metaModel->set('gor_has_login', [
            'label' => $this->_('Login'),
            'description' => $this->_('Can people login for this organization?'),
            'multiOptions' => $yesNo,
        ]);
        $this->metaModel->set('gor_add_respondents', [
            'label' => $this->_('Accepting'),
            'description' => $this->_('Can new respondents be added to the organization?'),
            'multiOptions' => $yesNo,
        ]);
        $this->metaModel->set('gor_has_respondents', [
            'label' => $this->_('Respondents'),
            'description' => $this->_('Does the organization have respondents?'),
            'multiOptions' => $yesNo,
        ]);
        $this->metaModel->set('gor_respondent_group', [
            'label' => $this->_('Respondent group'),
            'description' => $this->_('Allows respondents to login.'),
            'multiOptions' => $this->groupRepository->getRespondentGroupOptions(),
        ]);
        $this->metaModel->set('gor_accessible_by', [
            'label' => $this->_('Accessible by'),
            'description' => $this->_('Checked organizations see this organizations respondents.'),
            'multiOptions' => $this->organizationRepository->getOrganizations(),
            'type' => new ConcatenatedType(':', $this->_(', '), true),
        ]);

        $this->metaModel->setIfExists('gor_allowed_ip_ranges');

        if ($definitions) {
            reset($definitions);
            $this->metaModel->setIfExists('gor_user_class', [
                'label' => $this->_('User Definition'),
                'default' => key($definitions),
                'multiOptions' => $definitions,
            ]);
            if (1 == count($definitions)) {
                $this->metaModel->setIfExists('gor_user_class', [
                    'elementClass' => 'None',
                ]);
            }
        }

        $this->metaModel->setIfExists('gor_respondent_edit', [
            'label' => $this->_('Respondent edit screen'),
            'multiOptions' => $empty + $this->screenLoader->listRespondentEditScreens(),
        ]);
        $this->metaModel->setIfExists('gor_respondent_show', [
            'label' => $this->_('Respondent show screen'),
            'multiOptions' => $empty + $this->screenLoader->listRespondentShowScreens(),
        ]);
        $this->metaModel->setIfExists('gor_respondent_subscribe', [
            'label' => $this->_('Subscribe screen'),
            'multiOptions' => $this->screenLoader->listSubscribeScreens(),
        ]);
        $this->metaModel->setIfExists('gor_respondent_unsubscribe', [
            'label' => $this->_('Unsubscribe screen'),
            'multiOptions' => $this->screenLoader->listUnsubscribeScreens(),
        ]);
        $this->metaModel->setIfExists('gor_token_ask', [
            'label' => $this->_('Token ask screen'),
            'multiOptions' => $empty + $this->screenLoader->listTokenAskScreens(),
        ]);

        $this->metaModel->setIfExists('gor_resp_change_event', [
            'label' => $this->_('Respondent change event'),
            'multiOptions' => $this->trackEvents->listRespondentChangedEvents(),
        ]);
        $this->metaModel->setIfExists('gor_iso_lang', [
            'label' => $this->_('Language'),
            'multiOptions' => $this->locale->getAvailableLanguages(),
        ]);
        if ($this->styles) {
            $this->metaModel->setIfExists('gor_style', [
                'label' => $this->_('Mail template'),
                'multiOptions' => $this->styles,
                'default' => 'gems'
            ]);
        }

        if ($setTranslations) {
            // As not all labels are set at this moment
            $this->metaModelLoader->addDatabaseTranslations($this->metaModel, false);
        }

        $this->metaModel->addTransformer(new OrganizationConfigurableUserDefinitionTransformer($this->userLoader));

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return static
     */
    public function applyDetailSettings($setTranslations = true): static
    {
        $empty = $this->translatedUtil->getEmptyDropdownArray();
        $staffTemplates = $empty + $this->commTemplateRepository->getCommTemplatesForTarget('staffPassword');

        $this->applyBrowseSettings(false);

        $this->metaModel->set('gor_welcome', [
            'label' => $this->_('Greeting'),
            'description' => $this->_('For emails and token forward screen.'),
            'elementClass' => 'Textarea',
            'rows' => 5
        ]);
        $this->metaModel->set('gor_signature', [
            'label' => $this->_('Signature'),
            'description' => $this->_('For emails and token forward screen.'),
            'elementClass' => 'Textarea',
            'rows' => 5
        ]);
        $this->metaModel->set('gor_create_account_template', [
            'label' => $this->_('Create Account template'),
            'default' => $this->commTemplateRepository->getCommTemplateForCode('accountCreate', 'staffPassword'),
            'multiOptions' => $staffTemplates,
        ]);
        $this->metaModel->set('gor_reset_pass_template',       [
            'label' => $this->_('Reset Password template'),
            'default' => $this->commTemplateRepository->getCommTemplateForCode('passwordReset', 'staffPassword'),
            'multiOptions' => $staffTemplates
        ]);
        $this->metaModel->set('gor_reset_tfa_template',       [
            'label' => $this->_('Reset TFA template'),
            'default' => $this->commTemplateRepository->getCommTemplateForCode('tfaReset', 'staffPassword'),
            'multiOptions' => $staffTemplates,
        ]);

        $this->metaModel->setIfExists('gor_allowed_ip_ranges', [
            'label' => $this->_('Allowed IP Ranges'),
            'description' => $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25'),
        ]);

        if ($setTranslations) {
            $this->metaModelLoader->addDatabaseTranslations($this->metaModel, true);
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return static
     */
    public function applyEditSettings(): static
    {
        $this->applyDetailSettings(false);
        $this->metaModel->resetOrder();

        $yesNo = $this->translatedUtil->getYesNo();

        // GENERAL TAB
        /*$html = Html::create()->h4($this->_('General'));
        $this->metaModel->set('general', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);*/
        $this->metaModel->set('gor_name', [
            'size' => 25,
            'validators[unique]' => new ModelUniqueValidator('gor_name', ['gsf_id_user']),
        ]);
        $this->metaModel->set('gor_location', [
            'size' => 50,
            'maxlength' => 255,
        ]);
        $this->metaModel->set('gor_task', [
            'size' => 25,
        ]);
        $this->metaModel->set('gor_url', [
            'size' => 50,
            'validators[url]' => 'ExistingUrl',
        ]);
        $this->metaModel->set('gor_sites');
        $this->metaModel->setIfExists('gor_code', [
            'size' => 10
        ]);
        $this->metaModel->set('gor_provider_id');
        $this->metaModel->setIfExists('gor_active', [
            'elementClass' =>'Checkbox',
        ]);

        // EMAIL TAB
        $html = Html::create()->h4($this->_('Email') . ' & ' . $this->_('Token'));
        $this->metaModel->set('emailAndToken', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);
        $this->metaModel->set('gor_contact_name', [
            'order' => $this->metaModel->getOrder('gor_active') + 1000,
            'size' => 25,
        ]);
        $this->metaModel->set('gor_contact_email', [
            'size' => 50,
            'validator' => 'SimpleEmail',
            'required' => true,
        ]);
        $this->metaModel->set('gor_contact_sms_from', [
            'size' => 50,
            'maxlength' => 11,
            'description' => $this->_('The from field for an sms.'),
        ]);
        $this->metaModel->set('gor_mail_watcher', [
            'label' => $this->_('Check cron job mail'),
            'description' => $this->_('If checked the organization contact will be mailed when the cron job does not run on time.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);
        $this->metaModel->set('gor_welcome', [
            'elementClass' => 'Textarea',
            'rows' => 5,
        ]);
        $this->metaModel->set('gor_signature', [
            'elementClass' => 'Textarea',
            'rows' => 5,
        ]);
        $this->metaModel->set('gor_create_account_template');
        $this->metaModel->set('gor_reset_pass_template');
        $this->metaModel->set('gor_reset_tfa_template');

        // ACCESS TAB
        $html = Html::create()->h4($this->_('Access'));
        $this->metaModel->set('access', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);
        $this->metaModel->set('gor_has_login', [
            'order' => $this->metaModel->getOrder('gor_reset_pass_template') + 1000,
            'elementClass' => 'CheckBox',
        ]);
        $this->metaModel->set('gor_add_respondents', [
            'elementClass' => 'CheckBox',
        ]);
        $this->metaModel->set('gor_has_respondents', [
            'default' => 0,
            'elementClass' => 'Exhibitor',
        ]);
        $this->metaModel->set('gor_respondent_group');
        $this->metaModel->set('gor_accessible_by', [
            'elementClass' => 'MultiCheckbox',
        ]);
        $this->metaModel->set('allowed', [
            'label' => $this->_('Can access'),
            'elementClass' => 'Html',
        ]);

        $this->metaModel->setIfExists('gor_allowed_ip_ranges', [
            'elementClass' => 'Textarea',
            'rows' => 4,
            'validator' => new IPRanges(),
        ]);
        $this->metaModel->setIfExists('gor_user_class');

        $definitions = $this->metaModel->get('gor_user_class', 'multiOptions');
        if ($definitions && (count($definitions) > 1)) {
            reset($definitions);
            // MD: Removed onchange because it does not play nice with the processAfterLoad and save methods in this class
            //     @@TODO: See if we can enable it when these methods are changed into a dependency
            $this->metaModel->setIfExists('gor_user_class', [
                'autoSubmit' => true,
                'default' => key($definitions),
                'required' => true,
            ]);
        }

        // INTERFACE TAB
        $html = Html::create()->h4($this->_('Interface'));
        $this->metaModel->set('interface', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
        ]);
        $this->metaModel->setIfExists('gor_respondent_edit', [
            'default' => '',
            'elementClass' => 'Radio',
        ]);
        $this->metaModel->setIfExists('gor_respondent_show', [
            'default' => '',
            'elementClass' => 'Radio',
        ]);
        $this->metaModel->setIfExists('gor_respondent_subscribe', [
            'default' => '',
            'elementClass' => 'Radio',
        ]);
        $this->metaModel->setIfExists('gor_respondent_unsubscribe', [
            'default' => '',
            'elementClass' => 'Radio',
        ]);

        $this->metaModel->setIfExists('gor_token_ask', [
            'default' => 'Gems\\Screens\\Token\\Ask\\ProjectDefaultAsk',
            'elementClass' => 'Radio',
        ]);

        $this->metaModel->setIfExists('gor_resp_change_event', [
            'order' => $this->metaModel->getOrder('gor_user_class') + 1000,
        ]);
        $this->metaModel->setIfExists('gor_iso_lang', [
            'order' => $this->metaModel->getOrder('gor_user_class') + 1010,
            'default' => $this->locale->getDefaultLanguage(),
        ]);

        if ($this->styles) {
            $this->metaModel->setIfExists('gor_style');
        }

//        $this->metaModel->addTransformer(new HtmlSanitizeTransformer([
//            'gor_welcome',
//            'gor_signature',
//        ]));

        $this->metaModelLoader->addDatabaseTranslations($this->metaModel, true);

        return $this;
    }
}
