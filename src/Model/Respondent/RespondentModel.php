<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Respondent;

use Gems\Communication\CommunicationRepository;
use Gems\Exception\RespondentAlreadyExists;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\GemsJoinModel;
use Gems\Model\MaskedModelTrait;
use Gems\Model\MetaModelLoader;
use Gems\Project\ProjectSettings;
use Gems\Repository\ConsentRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\StaffRepository;
use Gems\SnippetsActions\ApplyLegacyActionInterface;
use Gems\SnippetsActions\ApplyLegacyActionTrait;
use Gems\User\GemsUserIdGenerator;
use Gems\User\Mask\MaskRepository;
use Gems\User\User;
use Gems\Util\Localized;
use Gems\Util\Translated;
use Gems\Validator\OneOf;
use Laminas\Filter\Callback;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\SnippetsActions\Form\EditActionAbstract;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @since      Class available since version 1.0
 */
class RespondentModel extends GemsJoinModel implements ApplyLegacyActionInterface
{
    use ApplyLegacyActionTrait;
    use MaskedModelTrait;

    /**
     * Store the SSN hashed in the database and display only '*****'
     */
    const SSN_HASH = 1;

    /**
     * Do not use the SSN
     */
    const SSN_HIDE = 2;

    /**
     * Store the SSN as an decryptable value
     */
    const SSN_ENCRYPT = 4;

    /**
     * @var array Fieldname => label
     */
    protected array $_labels = [];

    /**
     *
     * @var array Of field names containing consents
     */
    public array $consentFields = ['gr2o_consent'];

    protected string $currentGroup = '';

    protected int $currentOrganizationId;

    protected User|null $currentUser;

    protected int $currentUserId;

    /**
     * Determines the algorithm used to hash the social security number
     */
    public string $hashAlgorithm = 'sha512';

    protected int $ssnMode = self::SSN_HASH;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        MaskRepository $maskRepository,
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly ConsentRepository $consentRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly Localized $localizedUtil,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ProjectSettings $project,
        protected readonly RespondentRepository $respondentRepository,
        protected readonly StaffRepository $staffRepository,
        protected readonly Translated $translatedUtil,
        protected readonly GemsUserIdGenerator $gemsUserIdGenerator,
    )
    {
        $this->currentUser    = $currentUserRepository->getCurrentUser();
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->currentOrganizationId = $currentUserRepository->getCurrentOrganizationId();

        $this->maskRepository = $maskRepository;

        parent::__construct('gems__respondents', $metaModelLoader, $sqlRunner, $translate, 'respondents2', true);

        $this->addTable('gems__respondent2org', ['grs_id_user' => 'gr2o_id_user'], true);
        $this->addTable('gems__reception_codes', ['gr2o_reception_code' => 'grc_id_reception_code'], false);

        // Set the key to resp2org
        $keys = $this->metaModel->getItemsFor(['table' => 'gems__respondent2org', 'key' => true]);
        $this->metaModel->setKeys($keys);

        $metaModelLoader->setChangeFields($this->metaModel, 'grs');
        $metaModelLoader->setChangeFields($this->metaModel, 'gr2o');

        $this->addColumn("CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END", 'row_class');
        $this->addColumn("CASE WHEN grc_success = 1 THEN 0 ELSE 1 END", 'resp_deleted');
        $this->addColumn('CASE WHEN gr2o_email IS NULL OR LENGTH(TRIM(gr2o_email)) = 0 THEN 1 ELSE 0 END', 'calc_email');

        $this->applySettings();
    }

    /**
     * Add the respondent name as a caclulated field to the model
     * @param MetaModelInterface $metaModel
     * @param string $label
     */
    public static function addNameToModel(MetaModelInterface $metaModel, $label)
    {
        $nameExpr['familyLast'] = "COALESCE(grs_last_name, '-')";
        $fieldList[] = 'grs_last_name';

        if ($metaModel->has('grs_partner_last_name')) {
            $nameExpr['partnerSep'] = "' - '";
            if ($metaModel->has('grs_partner_surname_prefix')) {
                $nameExpr['partnerPrefix'] = "COALESCE(CONCAT(' ', grs_partner_surname_prefix), '')";
                $fieldList[] = 'grs_partner_surname_prefix';
            }

            $nameExpr['partnerLast'] = "COALESCE(CONCAT(' ', grs_partner_last_name), '')";
            $fieldList[] = 'grs_partner_last_name';
        }
        $nameExpr['lastFirstSep'] = "', '";

        if ($metaModel->has('grs_first_name')) {
            if ($metaModel->has('grs_initials_name')) {
                $nameExpr['firstName']  = "COALESCE(grs_first_name, grs_initials_name, '')";
                $fieldList[] = 'grs_first_name';
                $fieldList[] = 'grs_initials_name';
            } else {
                $nameExpr['firstName']  = "COALESCE(grs_first_name, '')";
                $fieldList[] = 'grs_first_name';
            }
        } elseif ($metaModel->has('grs_initials_name')) {
            $nameExpr['firstName']  = "COALESCE(grs_initials_name, '')";
            $fieldList[] = 'grs_initials_name';
        }
        if ($metaModel->has('grs_surname_prefix')) {
            $nameExpr['familyPrefix']  = "COALESCE(CONCAT(' ', grs_surname_prefix), '')";
            $fieldList[] = 'grs_surname_prefix';
        }

        if ($metaModel->has('grs_partner_name_after') && $metaModel->has('grs_partner_last_name')) {
            $fieldList[] = 'grs_partner_name_after';

            $lastPrefix = isset($nameExpr['familyPrefix']) ? $nameExpr['familyPrefix'] . ', ' : '';
            $partnerPrefix = isset($nameExpr['partnerPrefix']) ? ', ' . $nameExpr['partnerPrefix'] : '';

            $columnExpr = "CASE 
                WHEN grs_partner_name_after = 0 AND grs_partner_name_after IS NOT NULL THEN
                    CONCAT(grs_partner_last_name, ' - ', $lastPrefix grs_last_name, " . $nameExpr['lastFirstSep'] . ', ' . $nameExpr['firstName'] .  "$partnerPrefix)
                ELSE 
                    CONCAT(" . implode(', ', $nameExpr) . ") 
                END";
        } else {
            $columnExpr = "CONCAT(" . implode(', ', $nameExpr) . ")";
        }


        $metaModel->set('name', [
            'label' => $label,
            'column_expression' => $columnExpr,
            'fieldlist' => $fieldList,
        ]);
    }

    public function applyAction(SnippetActionInterface $action): void
    {
        if (! $action->isDetailed()) {
            if (! $this->joinStore->hasTable('gr2o_id_organization')) {
                $this->addTable('gems__organizations', array('gr2o_id_organization' => 'gor_id_organization'));
                $options = $this->metaModel->get('gr2o_id_organization');
                $options['order'] = $this->metaModel->getOrder('gr2o_id_organization') + 1;
                $this->metaModel->set('gor_name', $options);
                $this->metaModel->del('gr2o_id_organization', 'label');
            }
        }
        if ($action->isEditing()) {
            if ($this->metaModel->has('grs_ssn')) {
                $this->metaModel->set('grs_ssn', ['autoSubmit' => 'blur']);
            }

            $organizationSettings['default'] = $this->currentOrganizationId;

            if ($this->currentUser === null || count($this->currentUser->getAllowedOrganizations()) == 1) {
                $organizationSettings['elementClass'] = 'Exhibitor';
            } else {
                $organizationSettings['multiOptions']  = $this->currentUser->getRespondentOrganizations();
            }
            $this->setIfExists('gr2o_id_organization', $organizationSettings);

            $this->metaModel->del('name', 'label');

            if ($action instanceof EditActionAbstract && $action->createData) {
                $this->metaModel->setMulti(['gr2o_changed', 'gr2o_changed_by', 'gr2o_created', 'gr2o_created_by'], [
                    'label' => null,
                    'elementClass' => 'None',
                ]);
            }
        }
        $event = new RespondentModelSetEvent($this, $action);
        $this->eventDispatcher->dispatch($event, RespondentModelSetEvent::class);
    }

    public function applySettings()
    {
        $this->initLabels();
        $this->metaModel->resetOrder();

        $ucfirst = new Callback(function($value) {
            if (is_string($value) && ($value === strtolower($value))) {
                return ucfirst($value);
            }
            return $value;
        });

        // IDENTIFICATION
        $this->currentGroup = $this->_('Identification');
        $this->setSSN();
        $this->setIfExists('gr2o_id_organization', [
            'elementClass' => 'Exhibitor',
            'multiOptions' => $this->organizationRepository->getOrganizationsWithRespondents(),
            ]);
        $this->setIfExists('gr2o_patient_nr', [
            'validator[uniquePatientnr]' => new ModelUniqueValidator('gr2o_patient_nr', 'gr2o_id_organization')
        ]);
        $this->metaModel->set('grs_id_user', [
            'elementClass' => 'Hidden',
        ]);
        $this->metaModel->setOnSave('grs_id_user', [$this->gemsUserIdGenerator, 'createGemsUserId']);

        // NAME
        if (isset($this->_labels['name']) && $this->_labels['name']) {
            self::addNameToModel($this->metaModel, $this->_labels['name']);
        }
        $this->setIfExists('grs_initials_name');
        $this->setIfExists('grs_first_name', [
            'filters[ucfirst]' => $ucfirst,
        ]);
        $this->setIfExists('grs_surname_prefix', [
            'description' => $this->_('de, ibn, Le, Mac, von, etc...'),
        ]);
        $this->setIfExists('grs_last_name', [
            'filters[ucfirst]' => $ucfirst,
            'required'         => true,
        ]);
        $this->setIfExists('grs_partner_surname_prefix', [
            'description' => $this->_('de, ibn, Le, Mac, von, etc...'),
        ]);
        $this->setIfExists('grs_partner_last_name', [
            'filters[ucfirst]' => $ucfirst,
        ]);
        $this->setIfExists('grs_partner_name_after', [
            'default' => 1,
            'description' => $this->_('Should the partner name be place after the family name?'),
            'elementClass' => 'radio',
            'separator' => ' ',
            'multiOptions' => $this->translatedUtil->getYesNo(),
        ]);

        // MEDICAL DATA
        $this->setIfExists('grs_gender', [
            'elementClass' => 'radio',
            'separator' => ' ',
            'multiOptions' => $this->translatedUtil->getGenderHello(),
        ]);
        $this->setIfExists('grs_birthday', ['elementClass' => 'Date']);
        $this->setIfExists('gr2o_id_physician');
        $this->setIfExists('gr2o_treatment');
        $this->setIfExists('gr2o_comments', ['elementClass' => 'Textarea', 'rows' => 4]);

        // CONTACT INFO
        $this->currentGroup = $this->_('Contact information');
        $this->setIfExists('gr2o_email', [
            'required' => true,
            'autoInsertNotEmptyValidator' => false
            ]);
        if ($this->metaModel->has('gr2o_email')) {
            $this->addColumn('CASE WHEN gr2o_email IS NULL OR LENGTH(TRIM(gr2o_email)) = 0 THEN 1 ELSE 0 END', 'calc_email');
            $this->setIfExists('calc_email', [
               'elementClass' => 'Checkbox',
                'required' => true,
                'order' => $this->metaModel->getOrder('gr2o_email') + 1,
                'validator' => new OneOf(
                    $this->_('Respondent has no e-mail'),
                    'gr2o_email',
                    $this->metaModel->get('gr2o_email', 'label')
                )
            ]);
        }
        $this->setIfExists('gr2o_mailable', [
            'elementClass' => 'radio',
            'separator' => ' ',
            'multiOptions' => $this->communicationRepository->getRespondentMailCodes(),
            ]);
        $this->setIfExists('grs_address_1', [
            'filters[ucfirst]' => $ucfirst,
        ]);
        $this->setIfExists('grs_address_2');
        $this->setIfExists('grs_zipcode');
        $this->setIfExists('grs_city', [
            'filters[ucfirst]' => $ucfirst,
        ]);
        $this->setIfExists('grs_iso_country', [
            'multiOptions' => $this->localizedUtil->getCountries(),
        ]);

        $this->setIfExists('grs_phone_1');
        $this->setIfExists('grs_phone_2');
        $this->setIfExists('grs_phone_3');
        $this->setIfExists('grs_phone_4');

        $this->currentGroup = $this->_('Settings');
        $this->setIfExists('grs_iso_lang', [
            'default' => $this->localizedUtil->getDefaultLanguage(),
            'elementClass' => 'radio',
            'multiOptions' => $this->localizedUtil->getLanguages(),
            'separator' => ' ',
        ]);
        $this->setIfExists('gr2o_consent', [
            'default' => $this->consentRepository->getDefaultConsent(),
            'description' => $this->_('Has the respondent signed the informed consent letter?'),
            'elementClass' => 'radio',
            'multiOptions' => $this->consentRepository->getUserConsentOptions(),
            'separator' => ' ',
        ]);
        foreach ($this->consentFields as $consent) {
            $this->addColumn($consent, 'old_' . $consent);
            $this->metaModel->set('old_' . $consent, ['elementClass' => 'hidden']);
        }

        $changers = Late::method($this->staffRepository, 'getStaff');
        $this->setIfExists('gr2o_opened', [
            'default' => date('Y-m-d H:i:s'),
            'elementClass' => 'None',  // Has little use to show: is now
        ]);

        $this->setIfExists('gr2o_opened_by', [
            'default' => $this->currentUserId,
            'elementClass' => 'Hidden',  // Has little use to show: is usually editor, but otherwise is set to null during save
            'multiOptions' => $changers
        ]);
        $this->setIfExists('gr2o_changed');
        $this->setIfExists('gr2o_changed_by', [
            'multiOptions' => $changers,
        ]);
        $this->setIfExists('gr2o_created');
        $this->setIfExists('gr2o_created_by',  [
            'multiOptions' => $changers,
        ]);

        $event = new RespondentModelSetEvent($this);
        $this->eventDispatcher->dispatch($event, RespondentModelSetEvent::class);

        $this->applyMask();
    }

    public function checkIds(array $newValues)
    {
        $id = false;
        if (empty($newValues['grs_id_user'])) {
            if (! empty($newValues['gr2o_id_user'])) {
                $id = $newValues['gr2o_id_user'];
            } elseif (isset($newValues['gr2o_patient_nr'], $newValues['gr2o_id_organization'])) {
                $id = $this->respondentRepository->getRespondentId($newValues['gr2o_patient_nr'], intval($newValues['gr2o_id_organization']));
            }
        } else {
            $id = $newValues['grs_id_user'];
        }

        if (! empty($newValues['grs_ssn'])) {
            $ssnId = $this->respondentRepository->getRespondentIdBySsn($newValues['grs_ssn']);

            if ($ssnId && (! $id)) {
                $id = $ssnId;
            }
        } else {
            $ssnId = false;
        }

        if ($id) {
            if ($ssnId && ($id != $ssnId)) {
                // Log duplicate SSN?
                unset($newValues['grs_ssn']);
            }
            $newValues['grs_id_user']  = $id;
            $newValues['gr2o_id_user'] = $id;
        }

        return $newValues;
    }

    /**
     * Copy a respondent to a new organization
     *
     * If you want to share a respondent(id) with another organization use this
     * method.
     *
     * @param int $fromOrgId            Id of the sending organization
     * @param string $fromPid           Respondent number of the sending organization
     * @param int $toOrgId              Id of the receiving organization
     * @param string $toPid             Respondent number of the sending organization
     * @param bool $keepConsent         Should new organization inherit the consent of the old organization or not?
     * @return array The new respondent
     * @throws \Gems\Exception|RespondentAlreadyExists
     */
    public function copyToOrg($fromOrgId, $fromPid, $toOrgId, $toPid, $keepConsent = false)
    {
        // Maybe we should disable masking, just to be sure
        $this->maskRepository->disableMaskRepository();

        // Do some sanity checks
        $fromPatient = $this->loadFirst(['gr2o_id_organization' => $fromOrgId, 'gr2o_patient_nr' => $fromPid]);
        if (empty($fromPatient)) {
            throw new \Gems\Exception($this->_('Respondent not found in sending organization.'));
        }

        $toPatientByPid        = $this->loadFirst(['gr2o_id_organization' => $toOrgId, 'gr2o_patient_nr' => $toPid]);
        $toPatientByRespondent = $this->loadFirst(['gr2o_id_organization' => $toOrgId, 'gr2o_id_user' => $fromPatient['gr2o_id_user']]);
        if (!empty($toPatientByPid) && $toPatientByPid['gr2o_id_user'] == $fromPatient['gr2o_id_user']) {
            // We already have the same respondent, nothing to do
            throw new RespondentAlreadyExists($this->_('Respondent already exists in destination organization.'), 200, null, RespondentAlreadyExists::SAME);
        }
        if (!empty($toPatientByPid) && empty($toPatientByRespondent)) {
            // Could be the same, or someone else... just return an error for now maybe offer to mark as duplicate and merge records later on
            throw new RespondentAlreadyExists($this->_('Respondent with requested respondent number already exists in receiving organization.'), 200, null, RespondentAlreadyExists::OTHERUID);
        }
        if (!empty($toPatientByRespondent)) {
            // No action needed, maybe also report the number we found?
            throw new RespondentAlreadyExists($this->_('Respondent already exists in destination organization, but with different respondent number.'), 200, null, RespondentAlreadyExists::OTHERPID);
        }

        // Ready to go, unset consent if needed
        $toPatient = $fromPatient;
        if (!$keepConsent) {
            unset($toPatient['gr2o_consent']);
        }

        // And save the record
        $toPatient['gr2o_patient_nr']      = $toPid;
        $toPatient['gr2o_id_organization'] = $toOrgId;
        $toPatient['gr2o_reception_code']  = ReceptionCodeRepository::RECEPTION_OK;
        $result = $this->save($toPatient);

        // Now re-enable the mask feature
        $this->maskRepository->enableMaskRepository();

        return $result;
    }

    public function hideSSN($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        if ($value && (! $isPost)) {
            $this->metaModel->set('grs_ssn', 'description', $this->_('Empty this field to remove the SSN'));
            return str_repeat('*', 9);
        } else {
            return $value;
        }
    }

    public function initLabels(): void
    {
        if (! $this->_labels) {
            $this->_labels = [
                'gr2o_patient_nr' => $this->_('Respondent number'),
                'gr2o_id_organization' => $this->_('Organization'),
                'gr2o_id_physician' => $this->_('Physician'),
                'gr2o_treatment' => $this->_('Treatment'),
                'gr2o_email' => $this->_('E-Mail'),
                'calc_email' => $this->_('Respondent has no e-mail'),
                'gr2o_mailable' => $this->_('May be mailed'),
                'gr2o_comments' => $this->_('Comments'),
                'gr2o_consent' => $this->_('Consent'),
                'gr2o_reception_code' => $this->_('Reception code'),

                'gr2o_opened' => $this->_('Opened on'),
                'gr2o_opened_by' => $this->_('Opened by'),
                'gr2o_changed' => $this->_('Changed on'),
                'gr2o_changed_by' => $this->_('Changed by'),
                'gr2o_created' => $this->_('Created on'),
                'gr2o_created_by' => $this->_('Created by'),

                'grs_ssn' => $this->_('SSN'),
                'grs_iso_lang' => $this->_('Language'),

                'grs_initials_name' => $this->_('Initials'),
                'grs_first_name' => $this->_('First name'),
                'grs_surname_prefix' => $this->_('Surname prefix'),
                'grs_last_name' => $this->_('Family name'),
                'grs_partner_surname_prefix' => $this->_('Partner surname prefix'),
                'grs_partner_last_name' => $this->_('Partner family name'),
                'grs_partner_name_after' => $this->_('Partner name after family name'),
                'name' => $this->_('Name'),

                'grs_gender' => $this->_('Gender'),
                'grs_birthday' => $this->_('Birthday'),

                'grs_address_1' => $this->_('Street'),
                'grs_address_2' => $this->_(' '),
                'grs_zipcode' => $this->_('Zipcode'),
                'grs_city' => $this->_('Street'),
                'grs_region' => $this->_('City'),
                'grs_iso_country' => $this->_('Country'),

                'grs_phone_1' => trim(sprintf($this->_('Phone %s'), '')),
                'grs_phone_2' => sprintf($this->_('Phone %s'), 2),
                'grs_phone_3' => sprintf($this->_('Phone %s'), 3),
                'grs_phone_4' => sprintf($this->_('Phone %s'), 4),
            ];

            $event = new RespondentModelInitEvent($this, $this->_labels);
            $this->eventDispatcher->dispatch($event, RespondentModelInitEvent::class);
            $this->_labels = $event->getLabels();
        }
    }

    protected function setIfExists(string $name, array $options = []): bool
    {
        if ($this->metaModel->has($name)) {
            if ((!isset($options['label'])) && isset($this->_labels[$name]) && $this->_labels[$name]) {
                $options['label'] = $this->_labels[$name];

            }
            if ($this->currentGroup) {
                $options['tab'] = $this->currentGroup;
            }
            if (isset($options['label']) && $options['label']) {
                $this->metaModel->set($name, $options);
                return true;
            }
        }
        return false;
    }

    protected function setIfMultiple(array $names, array $options = []): void
    {
        foreach ($names as $name) {
            $this->setIfExists($name, $options);
        }
    }

    /**
     *
     * @param array $newValues The values to store for a single model item.
     * @return int Number of consent changes logged
     */
    public function logConsentChanges(array $newValues): int
    {
        $logModel = $this->metaModel->getMetaModelLoader()->createModel(RespondentConsentLogModel::class);

        $changes = 0;
        foreach ($this->consentFields as $consent) {
            $oldConsent = 'old_' . $consent;
            if (isset($newValues['gr2o_id_user'], $newValues['gr2o_id_organization'], $newValues[$consent]) &&
                array_key_exists($oldConsent, $newValues) &&  // Old consent can be empty
                ($newValues[$consent] != $newValues[$oldConsent]) ) {

                $values['glrc_id_user']         = $newValues['gr2o_id_user'];
                $values['glrc_id_organization'] = $newValues['gr2o_id_organization'];
                $values['glrc_consent_field']   = $consent;
                $values['glrc_old_consent']     = $newValues[$oldConsent];
                $values['glrc_new_consent']     = $newValues[$consent];
                $values['glrc_created']         = "CURRENT_TIMESTAMP";
                $values['glrc_created_by']      = $this->currentUserId;

                $logModel->save($values);
                $changes++;
            }
        }

        return $changes;
    }

    /**
     * Move a respondent to a new organization and/or change it's number
     *
     * This not be a copy, it will be renamed. Use copyToOrg if you want to make a copy.
     *
     * @param int $fromOrgId            Id of the sending organization
     * @param string $fromPid           Respondent number of the sending organization
     * @param int $toOrgId              Id of the receiving organization
     * @param string $toPid             Respondent number of the sending organization
     * @return array The new respondent
     * @throws RespondentAlreadyExists
     */
    public function move($fromOrgId, $fromPid, $toOrgId, $toPid)
    {
        // Maybe we should disable masking, just to be sure
        $this->maskRepository->disableMaskRepository();

        $patientFrom = $this->loadFirst([
            'gr2o_id_organization' => $fromOrgId,
            'gr2o_patient_nr'      => $fromPid
        ]);
        $patientTo = $this->loadFirst([
            'gr2o_id_organization' => $toOrgId,
            'gr2o_patient_nr'      => $toPid
        ]);

        // Lookup change and create filter
        $filter = [];
        if ($fromPid !== $toPid) {
            $patientFrom['gr2o_patient_nr'] = $toPid;
            $filter['gr2o_patient_nr'] = $fromPid;
        }
        if ($fromOrgId !== $toOrgId) {
            $patientFrom['gr2o_id_organization'] = $toOrgId;
            $filter['gr2o_id_organization'] = $fromOrgId;
        }

        if ($filter && empty($patientTo)) {
            $result = $this->save($patientFrom, $filter);
        } else {
            // already exists, return current patient
            // we can not delete the other records, maybe mark as inactive or throw an error
            $this->maskRepository->enableMaskRepository();
            throw new RespondentAlreadyExists($this->_('Respondent already exists in destination, please delete current record manually if needed.'), 200, null, RespondentAlreadyExists::OTHERUID);
        }

        // Now re-enable the mask feature
        $this->maskRepository->enableMaskRepository();

        return $result;
    }

    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
//        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($newValues, true) . "\n", FILE_APPEND);
//        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($filter, true) . "\n", FILE_APPEND);

        $newValues = $this->checkIds($newValues);

        $output = parent::save($newValues, $filter, $saveTables);

        $this->logConsentChanges($output);

        if (isset($output['gr2o_id_organization']) && isset($output['grs_id_user'])) {
            // Tell the organization it has at least one user
            $org = $this->organizationRepository->getOrganization(intval($output['gr2o_id_organization']));
            if ($org) {
                $org->setHasRespondents($this->currentUserId);
            }

            $respondent = $this->respondentRepository->getRespondent($output['gr2o_patient_nr'], intval($output['gr2o_id_organization']), intval($output['grs_id_user']));
            $respondent->handleChanged();
        }

        return $output;
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return mixed The salted hash as a 32-character hexadecimal number.
     */
    public function saveSSN($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            switch ($this->ssnMode) {
                case self::SSN_HASH:
                    return $this->project->getValueHash($value, $this->hashAlgorithm);

                case self::SSN_ENCRYPT:
                    return $this->project->encrypt($value);
                    break;

                case self::SSN_HIDE:
                default:
                    return null;
            }
        }
        return $value;
    }

    public function setSSN()
    {
        if ($this->metaModel->has('grs_ssn')) {
            switch ($this->ssnMode) {
                case self::SSN_HASH:
                case self::SSN_ENCRYPT:
                    $this->metaModel->setSaveWhen('grs_ssn', [$this, 'whenSSN']);
                    $this->metaModel->setOnLoad('grs_ssn', [$this, 'hideSSN']);
                    $this->metaModel->setOnSave('grs_ssn', [$this, 'saveSSN']);
                    break;

                case self::SSN_HIDE:
                default:
                    // Do not display
                    $this->metaModel->del('grs_ssn', 'label');
                    return;
            }
        } else {
            $this->ssnMode = self::SSN_HIDE;
        }

        $this->setIfExists('grs_ssn');
    }

    public function whenSSN($value, $isNew = false, $name = null, array $context = array())
    {
        if (! $value) {
            return true;
        }
        return $value && ($value !== $this->hideSSN($value, $isNew, $name, $context));
    }
}