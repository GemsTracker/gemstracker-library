<?php

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Mask;

use MUtil\Translate\TranslateableTrait;

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Dec 25, 2016 4:36:18 PM
 */
class MaskStore extends \Gems_Loader_TargetLoaderAbstract
{
    use TranslateableTrait;

    /**
     *
     * @var array Of hidden model settings
     */
    private $_compiledHiddenModelSettings;

    /**
     *
     * @var array Of non-hidden model settings
     */
    private $_compiledNormalModelSettings;

    /**
     * Loaded in afterRegistry, derived wholly from $_settings
     *
     * @var array of [fieldname => groupname]
     */
    protected $_fieldList;

    /**
     * Loaded in _ensureSettings
     *
     * @var array of [groupname => [label, description, class, maskFields, masker]
     */
    protected $_settings;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'User\\Mask';

    /**
     * Array containing default data
     *
     * @var array
     */
    protected $defaultData;

    /**
     *
     * @var string Name separator for creating unique field names
     */
    protected $keySeparator = '___';

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
            'grs_birthday' => 'mask',
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
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string $storageField
     * @return $this
     */
    public function addMaskSettingsToModel(\MUtil_Model_ModelAbstract $model, $storageField)
    {
        $model->set($storageField, 'elementClass', 'Hidden');
        $model->setOnSave($storageField, [$this, 'saveSettings']);

        $html = \MUtil_Html::create()->h4($this->_('Privacy settings'));
        $model->set($storageField . '__HEADER', 'label', ' ',
                'default', $html,
                'elementClass', 'Html',
                'value', $html
                );

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
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();

        $this->_ensureSettings();

        // Load the masker classes
        foreach ($this->_settings as $name => $setting) {
            if (isset($setting['class'], $setting['maskFields'])) {
                $this->_settings[$name]['masker'] = $this->_loadClass(
                        $setting['class'],
                        true,
                        array($setting['maskFields'])
                        );
            }
        }

        // Create the fieldlist
        $this->_fieldList = array();
        foreach ($this->_settings as $name => $setting) {
            if (isset($setting['class'], $setting['maskFields'])) {
                foreach ($setting['maskFields'] as $field => $type) {
                    $this->_fieldList[$field] = $name;
                }
            }
        }
    }

    /**
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param boolean $hideWhollyMasked When true the labels of wholly masked items are removed
     * @return $this
     */
    public function applyMaskDataToModel(\MUtil_Model_ModelAbstract $model, $hideWhollyMasked = false)
    {
        if ($hideWhollyMasked) {
            $compiled = $this->_compiledHiddenModelSettings;
        } else {
            $compiled = $this->_compiledNormalModelSettings;
        }
        // \MUtil_Echo::track($hideWhollyMasked, (boolean) $compiled);
        if (! is_array($compiled)) {
            $compiled = [];
            foreach ($this->_settings as $name => $setting) {
                if (isset($setting['masker']) && $setting['masker'] instanceof MaskerInterface) {

                    if ($model->hasAnyOf($setting['masker']->getMaskFields())) {

                        $dataOptions = $setting['masker']->getDataModelOptions($hideWhollyMasked);

                        // \MUtil_Echo::track($name, count($dataOptions));
                        if ($dataOptions) {
                            foreach ($dataOptions as $field => $options) {
                                // \MUtil_Echo::track($hideWhollyMasked, $name, $field, array_keys($options));
                                $compiled[$field] = $options;
                            }
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
        if ($hideWhollyMasked) {
            $this->_compiledHiddenModelSettings = $compiled;
        } else {
            $this->_compiledNormalModelSettings = $compiled;
        }
        return $this;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of decoding the json settings
     * to an array value
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string|null
     */
    public function decodeSettings($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value) {
            return json_decode($value, true);
        }
        return $this->defaultData;
    }

    /**
     *
     * @return array of [groupname => [label, description, class, maskFields, masker]
     */
    public function getSettings()
    {
        return $this->_settings;
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
     *
     * @param string $fieldName
     * @return boolean True if this field is invisible
     */
    public function isFieldInvisible($fieldName)
    {
        if (isset($this->_fieldList[$fieldName])) {
            $group = $this->_fieldList[$fieldName];

            if (isset($this->_settings[$group], $this->_settings[$group]['masker']) &&
                    $this->_settings[$group]['masker'] instanceof \Gems\User\Mask\MaskerInterface) {
                return $this->_settings[$group]['masker']->isFieldInvisible($fieldName);
            }
        }

        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial($fieldName)
    {
        if (isset($this->_fieldList[$fieldName])) {
            $group = $this->_fieldList[$fieldName];

            if (isset($this->_settings[$group], $this->_settings[$group]['masker']) &&
                    $this->_settings[$group]['masker'] instanceof \Gems\User\Mask\MaskerInterface) {
                return $this->_settings[$group]['masker']->isFieldMaskedPartial($fieldName);
            }
        }

        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole($fieldName)
    {
        if (isset($this->_fieldList[$fieldName])) {
            $group = $this->_fieldList[$fieldName];

            if (isset($this->_settings[$group], $this->_settings[$group]['masker']) &&
                    $this->_settings[$group]['masker'] instanceof \Gems\User\Mask\MaskerInterface) {
                return $this->_settings[$group]['masker']->isFieldMaskedWhole($fieldName);
            }
        }

        return false;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming settings
     * to values
     *
     * @see \MUtil_Model_ModelAbstract
     *
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
     *
     * @param array $row A row of data to mask
     * @return array A row with all data masked
     */
    public function maskRow(array $row)
    {
        $settings = $this->getSettings();

        foreach ($settings as $name => $setting) {
            if (isset($setting['masker']) && $setting['masker'] instanceof MaskerInterface) {
                if (array_intersect(array_keys($row), $setting['masker']->getMaskFields())) {
                    $setting['masker']->maskRow($row);
                }
            }
        }

        return $row;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns fields as a single storage string
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function saveSettings($value, $isNew = false, $name = null, array $context = array())
    {
        $output = [];

        foreach ($context as $key => $value) {
            $setting = $this->getSettingsField($key);
            if ($setting) {
                // \MUtil_Echo::track($setting, $value);
                $output[$setting] = $value;
            }
        }
        // \MUtil_Echo::track($name, $output, json_encode($output));

        return json_encode($output);
    }

    /**
     *
     * @param array $maskData Saved values set for this user
     * @return $this
     */
    public function setMaskSettings(array $maskData)
    {
        $this->_compiledHiddenModelSettings = false;
        $this->_compiledNormalModelSettings = false;

        foreach ($this->_settings as $name => $setting) {
            if (isset($setting['masker']) && $setting['masker'] instanceof MaskerInterface) {

                $choice = isset($maskData[$name]) ? $maskData[$name] : $setting['masker']->getSettingsDefault();

                $setting['masker']->setChoice($choice);
            }
        }

        return $this;
    }
}
