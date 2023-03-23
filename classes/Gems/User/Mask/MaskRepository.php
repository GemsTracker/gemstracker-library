<?php

namespace Gems\User\Mask;

use Gems\Db\ResultFetcher;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Predicate;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class MaskRepository
{
    use TranslateableTrait;

    /**
     * Loaded in _ensureFields, derived wholly from $_settings
     *
     * @var array of [fieldname => groupname]
     */
    protected array $_fieldList = [];

    /**
     * Loaded in _ensureSettings in the constructor     *
     * @var array of [groupname => [label, description, class, maskFields, masker]
     */
    protected array $_settings;

    /**
     * @var array Array containing default data, filled in adddMaskSettings
     */
    protected array $defaultData = [];

    /**
     *
     * @var string Name separator for creating unique field names
     */
    protected $keySeparator = '___';

    protected $overloader;

    public function __construct(
        protected CurrentUserRepository $currentUserRepository,
        ProjectOverloader $projectOverloader,
        protected ResultFetcher $resultFetcher,
        TranslatorInterface $translate,
    )
    {
        $this->overloader = $projectOverloader->createSubFolderOverloader('User\\Mask');
        $this->translate = $translate;

        $this->_ensureSettings();
    }

    protected function _ensureFieldList()
    {
        if (! $this->_fieldList) {
            // Load the masker classes
            foreach ($this->_settings as $name => $setting) {
                if (isset($setting['class'], $setting['maskFields']) && (! isset($this->_settings[$name]['masker']))) {
                    $this->_settings[$name]['masker'] = $this->overloader->create(
                        $setting['class'],
                        $setting['maskFields']
                    );
                }
            }

            // Create the fieldlist
            foreach ($this->_settings as $name => $setting) {
                if (isset($setting['class'], $setting['maskFields'])) {
                    foreach ($setting['maskFields'] as $field => $type) {
                        $this->_fieldList[$field] = $name;
                    }
                }
            }
        }
    }

    /**
     * Set $this->_settings to array of [groupname => [label, description, class, maskFields]
     *
     * @return void
     */
    protected function _ensureSettings()
    {
        $this->_settings = [
            'name' => [
                'label'       => $this->_('Mask respondent name'),
                'description' => $this->_('Hide name and e-mail address.'),
                'class'       => 'NameMasker',
                'maskFields'  => $this->_getNameFields(),
            ],
            'gender' => [
                'label'       => $this->_('Mask gender'),
                'description' => $this->_('Hide gender'),
                'class'       => 'AnyMasker',
                'maskFields'  => $this->_getGenderFields(),
            ],
            'birthday' => [
                'label'       => $this->_('Mask birthday'),
                'description' => $this->_('Hide (parts of) a birthday'),
                'class'       => 'BirthdayMasker',
                'maskFields'  => $this->_getBirthdayFields(),
            ],
            'address' => [
                'label'       => $this->_('Mask address'),
                'description' => $this->_('Hide (parts of) the address'),
                'class'       => 'AddressMasker',
                'maskFields'  => $this->_getAddressFields(),
            ],
            'phone' => [
                'label'       => $this->_('Mask phones'),
                'description' => $this->_('Mask phone fields'),
                'class'       => 'AnyMasker',
                'maskFields'  => $this->_getPhoneFields(),
            ],
            'healthdata' => [
                'label'       => $this->_('Mask health data'),
                'description' => $this->_('Mask healt data, e.g. scores and health comments'),
                'class'       => 'AnyMasker',
                'maskFields'  => $this->_getHealthDataFields(),
            ],
        ];
    }

    /**
     * Get the possible address fields
     *
     * @return array of [fieldname => masker class specific setting]
     */
    protected function _getAddressFields()
    {
        return [
            'grs_address_1'   => 'mask',
            'grs_address_2'   => 'hide',
            'grs_zipcode'     => 'zip',
            'grs_city'        => 'city',
            'grs_region'      => 'mask',
            'grs_iso_country' => 'country',
        ];
    }

    /**
     * Get the possible birthday fields
     *
     * @return array of [fieldname => masker class specific setting]
     */
    protected function _getBirthdayFields()
    {
        return [
            'grs_birthday'  => 'mask',
            'grr_birthdate' => 'mask',
        ];
    }

    /**
     * Get the possible gender fields
     *
     * @return array of [fieldname => masker class specific setting]
     */
    protected function _getGenderFields()
    {
        return [
            'grs_gender' => 'hide',
            'grr_gender' => 'hide',
        ];
    }

    /**
     * Get the possible healt data fields
     *
     * @return array of [fieldname => masker class specific setting]
     */
    protected function _getHealthDataFields()
    {
        return [
            'gr2o_comments' => 'mask',
            'gr2t_comment'  => 'mask',
            'gto_result'    => 'mask',
            'gr2t_comment'  => 'mask',
            'gap_comment'   => 'mask',
            'gec_subject'   => 'mask',
            'gec_comment'   => 'mask',
            'gec_diagnosis' => 'mask',
        ];
    }

    /**
     * Get the possible name fields
     *
     * @return array of [fieldname => masker class specific setting]
     */
    protected function _getNameFields()
    {
        return [
            'grs_initials_name'          => 'short',
            'grs_first_name'             => 'mask',
            'grs_surname_prefix'         => 'hide',
            'grs_last_name'              => 'mask',
            'grs_partner_surname_prefix' => 'hide',
            'grs_partner_last_name'      => 'hide',
            'gr2o_email'                 => 'mask',
            'name'                       => 'double',
            'respondent_name'            => 'double',
            'grco_address'               => 'mask',
            'grr_email'                  => 'mask',
            'grr_initials_name'          => 'hide',
            'grr_first_name'             => 'mask',
            'grr_surname_prefix'         => 'hide',
            'grr_last_name'              => 'mask',
            'grr_partner_surname_prefix' => 'hide',
            'grr_partner_last_name'      => 'hide',
        ];
    }

    /**
     * Get the possible phone fields
     *
     * @return array of [fieldname => masker class specific setting]
     */
    protected function _getPhoneFields()
    {
        return [
            'grs_phone_1' => 'hide',
            'grs_phone_2' => 'hide',
            'grs_phone_3' => 'hide',
            'grs_phone_4' => 'hide',
            'grs_phone_5' => 'hide',
        ];
    }

    /**
     * @param MetaModelInterface $model
     * @param string $storageField
     * @param bool $detailed
     * @return $this
     */
    public function addMaskStorageTo(MetaModelInterface $model, string $storageField, bool $detailed = true)
    {
        $this->_ensureFieldList();

        $model->set($storageField, [
            'elementClass' => 'Hidden',
            ]);
        $model->setOnSave($storageField, [$this, 'saveSettings']);

        if ($detailed) {
            $html = Html::create()->h4($this->_('Privacy settings'));
            $model->set($storageField . '__HEADER', [
                'label' => ' ',
                'default' => $html,
                'elementClass' => 'Html',
                SqlRunnerInterface::NO_SQL => true,
                'value' => $html,
            ]);
        }

        foreach ($this->_settings as $name => $setting) {
            if (isset($setting['masker']) && $setting['masker'] instanceof MaskerInterface) {
                $key = $this->makeKey($storageField, $name);

                $masker = $setting['masker'];

                // Cleanup settings not for model
                unset($setting['class'], $setting['maskFields'], $setting['masker']);

                $maskSettings = $masker->getSettingOptions();

                $this->defaultData[$name] = $masker->getSettingsDefault();

                $model->set($key, $maskSettings + $setting);
                $model->setOnLoad($key, [$this, 'loadSettings']);
            }
        }

        return $this;
    }

    /**
     *
     * @param MetaModelInterface $model
     * @param boolean $hideWhollyMasked When true the labels of wholly masked items are removed
     * @return $this
     */
    public function applyMaskToDataModel(MetaModelInterface $model, $hideWhollyMasked = false)
    {
        $this->_ensureFieldList();

        $user = $this->currentUserRepository->getCurrentUser();
        $currentGroup        = $user->getGroupId(true);
        $currentOrganization = $user->getCurrentOrganizationId();

        $groupLike = new Like('gm_groups', '%:' . $currentGroup . ':%');
        $groupNull = new Operator('gm_groups', Operator::OPERATOR_EQUAL_TO, '::');
        $orgLike = new Like('gm_organizations', '%:' . $currentOrganization . ':%');
        $orgNull = new Operator('gm_organizations', Operator::OPERATOR_EQUAL_TO, '::');

        $whereOr = new Predicate(
            [
                new Predicate([$groupLike, $orgNull], Predicate::COMBINED_BY_AND),
                new Predicate([$groupLike, $orgLike], Predicate::COMBINED_BY_AND),
                new Predicate([$groupNull, $orgLike], Predicate::COMBINED_BY_AND),
            ],
            Predicate::COMBINED_BY_OR);

        $baseGroup        = $user->getGroupId(false);
        $baseOrganization = $user->getBaseOrganizationId();
        if (($baseGroup != $currentGroup) || ($baseOrganization != $currentOrganization)) {
            $baseGroupLike = new Like('gm_groups', '%:' . $baseGroup . ':%');
            $baseGroupNull = new Operator('gm_groups', Operator::OPERATOR_EQUAL_TO, '::');
            $baseOrgLike   = new Like('gm_organizations', '%:' . $baseOrganization . ':%');
            $baseOrgNull   = new Operator('gm_organizations', Operator::OPERATOR_EQUAL_TO, '::');
            $sticky        = new Operator('gm_mask_sticky', Operator::OPERATOR_EQUAL_TO, 1);

            $whereOr->addPredicate(
                new Predicate([$baseGroupLike, $baseOrgNull, $sticky], Predicate::COMBINED_BY_AND)
            );
            $whereOr->addPredicate(
                new Predicate([$baseGroupLike, $baseOrgLike, $sticky], Predicate::COMBINED_BY_AND)
            );
            $whereOr->addPredicate(
                new Predicate([$baseGroupNull, $baseOrgLike, $sticky], Predicate::COMBINED_BY_AND)
            );
        }

        $select = $this->resultFetcher->getSelect('gems__masks');
        $select->columns(['gm_mask_settings'])
            ->where(['gm_mask_active' => 1])
            ->where($whereOr)
            ->order('gm_id_order');


        $maskRow = $this->resultFetcher->fetchOne($select);
        if ($maskRow) {
            $maskData = $this->decodeSettings($maskRow);
            dump($maskRow, $maskData);
        } else {
            return $this;
        }

        // \MUtil\EchoOut\EchoOut::track($hideWhollyMasked, (boolean) $compiled);
        $compiled = [];
        foreach ($this->_settings as $name => $setting) {
            if (isset($setting['masker']) && $setting['masker'] instanceof MaskerInterface) {
                if (isset($maskData[$name])) {
                    $setting['masker']->setChoice($maskData[$name]);
                }
                if ($model->hasAnyOf($setting['masker']->getMaskFields())) {
                    $dataOptions = $setting['masker']->getDataModelOptions($hideWhollyMasked);

                    // \MUtil\EchoOut\EchoOut::track($name, count($dataOptions));
                    if ($dataOptions) {
                        foreach ($dataOptions as $field => $options) {
                            $compiled[$field] = $options;
                        }
                    }
                }
            }
        }
        foreach ($compiled as $field => $options) {
            if ($model->has($field)) {
                $model->set($field, $options);
            }
        }

        return $this;
    }


    /**
     * @param mixed $value
     * @return array|null
     */
    public function decodeSettings($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value) {
            return \json_decode($value, true);
        }
        return $this->defaultData;
    }

    /**
     * Get the settings field name from a combined field name
     *
     * @param string $key
     * @return string The settings name or null when not a key name
     */
    public function getSettingsField($key)
    {
        $pos = strpos($key, $this->keySeparator);

        if ($pos) {
            return substr($key, $pos + strlen($this->keySeparator));
        }

        return null;
    }

    /**
     * Get the storage field name from a combined field name
     *
     * @param string $key
     * @return string The storage name or null when not a key name
     */
    public function getStorageField($key)
    {
        $pos = strpos($key, $this->keySeparator);

        if ($pos) {
            return substr($key, 0, $pos);
        }

        return null;
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string|null
     */
    public function loadSettings($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        if ($value) {
            return $value;
        }

        $setting = $this->getSettingsField($name);
        $storage = $this->getStorageField($name);

        if (isset($context[$storage])) {
            $decoded = $this->decodeSettings($context[$storage]);
        } else {
            $decoded = $this->defaultData;
        }
        // dump($value, $name, $decoded);
        if (isset($decoded[$setting])) {
            return $decoded[$setting];
        }

        return $value;
    }

    /**
     * Make a model field name
     *
     * @param string $storageField
     * @param string $settingsdField
     * @return string
     */
    public function makeKey($storageField, $settingsField)
    {
        return $storageField . $this->keySeparator . $settingsField;
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function saveSettings($value, $isNew = false, $name = null, array $context = array())
    {
        $output = [];

        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true) . "\n", FILE_APPEND);

        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($context, true) . "\n", FILE_APPEND);
        foreach ($context as $key => $value) {
            $setting = $this->getSettingsField($key);
            if ($setting) {
                // \MUtil\EchoOut\EchoOut::track($setting, $value);
                $output[$setting] = $value;
            }
        }
        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($output, true) . "\n", FILE_APPEND);

        return json_encode($output);
    }
}