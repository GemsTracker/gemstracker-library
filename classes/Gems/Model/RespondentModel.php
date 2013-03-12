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
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard Respondent model.
 *
 * When a project defines its own sub-class of this class and names
 * it <Project_name>_Model_RespondentModel, that class is loaded
 * instead.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_RespondentModel extends Gems_Model_HiddenOrganizationModel
{
    const SSN_HASH = 0;
    const SSN_HIDE = 1;
    const SSN_OPEN = 2;

    /**
     * Determines how the social security number is stored.
     *
     * Can be changed is derived classes.
     *
     * @var int One of the SSN_ constants
     */
    public $hashSsn = self::SSN_HASH;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var Gems_Util
     */
    protected $util;

    /**
     * Self constructor
     */
    public function __construct()
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('respondents', 'gems__respondents', 'grs');

        $this->addTable('gems__respondent2org', array('grs_id_user' => 'gr2o_id_user'), 'gr2o');
        $this->addTable('gems__reception_codes', array('gr2o_reception_code' => 'grc_id_reception_code'));

        $this->setKeys($this->_getKeysFor('gems__respondent2org'));

        $this->setOnSave('gr2o_opened', new MUtil_Db_Expr_CurrentTimestamp());
        $this->setSaveOnChange('gr2o_opened');
        $this->setOnSave('gr2o_opened_by', GemsEscort::getInstance()->session->user_id);
        $this->setSaveOnChange('gr2o_opened_by');

        if (! $this->has('grs_ssn')) {
            $this->hashSsn = self::SSN_HIDE;
        }
        if (self::SSN_HASH === $this->hashSsn) {
            $this->setSaveWhen('grs_ssn', array($this, 'whenSSN'));
            $this->setOnLoad('grs_ssn', array($this, 'hideSSN'));
            $this->setOnSave('grs_ssn', array($this, 'saveSSN'));
        }
    }

    /**
     * Add an organization filter if it wasn't specified in the filter.
     *
     * Checks the filter on sematic correctness and replaces the text seacrh filter
     * with the real filter.
     *
     * @param mixed $filter True for the filter stored in this model or a filter array
     * @return array The filter to use
     */
    protected function _checkFilterUsed($filter)
    {
        $filter = parent::_checkFilterUsed($filter);

        if (! isset($filter['gr2o_id_organization'])) {
            if ($this->isMultiOrganization() && !isset($filter['gr2o_patient_nr'])) {
                $allowed = $this->user->getAllowedOrganizations();

                // If we are not looking for a specific patient, we can look at all patients
                $filter['gr2o_id_organization'] = array_keys($allowed);
            } else {
                // Otherwise, we can only see in our current organization
                $filter['gr2o_id_organization'] = $this->getCurrentOrganization();
            }
        }

        if (self::SSN_HASH === $this->hashSsn) {
            // Make sure a search for a SSN is hashed when needed.
            array_walk_recursive($filter, array($this, 'applyHash'));
        }

        return $filter;
    }

    /**
     * Add the table and field to check for respondent login checks
     *
     * @return Gems_Model_RespondentModel (continuation pattern)
     */
    public function addLoginCheck()
    {
        $this->addLeftTable(
                'gems__user_logins',
                array('gr2o_patient_nr' => 'gul_login', 'gr2o_id_organization' => 'gul_id_organization'),
                'gul',
                MUtil_Model_DatabaseModelAbstract::SAVE_MODE_UPDATE |
                    MUtil_Model_DatabaseModelAbstract::SAVE_MODE_DELETE);

        $this->addColumn(
                "CASE WHEN gul_id_user IS NULL OR gul_user_class = 'NoLogin' OR gul_can_login = 0 THEN 0 ELSE 1 END",
                'has_login');

        return $this;
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Model_RespondentModel
     */
    public function applyBrowseSettings()
    {
        $dbLookup   = $this->util->getDbLookup();
        $translated = $this->util->getTranslated();
        $translator = $this->translate->getAdapter();

        $this->resetOrder();

        if ($this->has('gr2o_id_organization') && $this->isMultiOrganization()) {
            $this->set('gr2o_id_organization',
                    'label', $translator->_('Organization'),
                    'multiOptions', $dbLookup->getOrganizationsWithRespondents()
                    );
        }

        $this->setIfExists('gr2o_patient_nr', 'label', $translator->_('Respondent nr'));

        $this->set('name',
                'label', $translator->_('Name'),
                'column_expression', "CONCAT(
                    COALESCE(CONCAT(grs_last_name, ', '), '-, '),
                    COALESCE(CONCAT(grs_first_name, ' '), ''),
                    COALESCE(grs_surname_prefix, ''))",
                'fieldlist', array('grs_last_name', 'grs_first_name', 'grs_surname_prefix'));

        $this->setIfExists('grs_email',       'label', $translator->_('E-Mail'));

        $this->setIfExists('grs_address_1',   'label', $translator->_('Street'));
        $this->setIfExists('grs_zipcode',     'label', $translator->_('Zipcode'));
        $this->setIfExists('grs_city',        'label', $translator->_('City'));

        $this->setIfExists('grs_phone_1',     'label', $translator->_('Phone'));

        $this->setIfExists('grs_birthday',
                'label', $translator->_('Birthday'),
                'dateFormat', Zend_Date::DATE_MEDIUM);

        $this->setIfExists('gr2o_opened',
                'label', $translator->_('Opened'),
                'formatFunction', $translated->formatDateTime);
        $this->setIfExists('gr2o_consent',
                'label', $translator->_('Consent'),
                'multiOptions', $dbLookup->getUserConsents()
                );

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @param mixed $locale The locale for the settings
     * @return \Gems_Model_RespondentModel
     */
    public function applyDetailSettings($locale = null)
    {
        $dbLookup   = $this->util->getDbLookup();
        $localized  = $this->util->getLocalized();
        $translated = $this->util->getTranslated();
        $translator = $this->translate->getAdapter();

        $this->resetOrder();
        if ($this->has('gr2o_id_organization')) {
            $this->set('gr2o_id_organization',
                    'label', $translator->_('Organization'),
                    'tab', $translator->_('Identification')
                    );

            if ($this->isMultiOrganization()) {
                $user = $this->loader->getCurrentUser();

                $this->set('gr2o_id_organization',
                        'default', $user->getCurrentOrganizationId(),
                        'multiOptions', $user->getRespondentOrganizations()
                        );
            }
        }

        // The SSN
        if ($this->hashSsn !== Gems_Model_RespondentModel::SSN_HIDE) {
            $this->set('grs_ssn', 'label', $translator->_('SSN'),
                    'tab', $translator->_('Identification'));
        }

        $this->setIfExists('gr2o_patient_nr', 'label', $translator->_('Respondent number'),
                'tab', $translator->_('Identification'));

        $this->setIfExists('grs_first_name',  'label', $translator->_('First name'));
        $this->setIfExists('grs_last_name',   'label', $translator->_('Last name'));

        $this->setIfExists('grs_gender',
                'label', $translator->_('Gender'),
                'multiOptions', $translated->getGenderHello()
                );

        $this->setIfExists('grs_birthday',
                'label', $translator->_('Birthday'),
                'dateFormat', Zend_Date::DATE_MEDIUM
                );

        $this->setIfExists('gr2o_treatment',          'label', $translator->_('Treatment'));
        $this->setIfExists('gr2o_comments',           'label', $translator->_('Comments'));

        $this->setIfExists('grs_email',       'label', $translator->_('E-Mail'),
                'tab', $translator->_('Contact information'));

        $this->setIfExists('grs_address_1',   'label', $translator->_('Street'));
        $this->setIfExists('grs_address_2',   'label', $translator->_('&nbsp;'));

        // MUtil_Echo::track($this->getItemsOrdered());
        //MUtil_Echo::track($this->getItemsOrdered(), $this->getOrder('grs_email'));

        $this->setIfExists('grs_zipcode',     'label', $translator->_('Zipcode'));
        $this->setIfExists('grs_city',        'label', $translator->_('City'));
        $this->setIfExists('grs_iso_country', 'label', $translator->_('Country'),
                'multiOptions', $localized->getCountries());

        $this->setIfExists('grs_phone_1',     'label', $translator->_('Phone'));
        $this->setIfExists('grs_phone_2',     'label', $translator->_('Phone 2'));
        $this->setIfExists('grs_phone_3',     'label', $translator->_('Phone 3'));

        $this->setIfExists('grs_iso_lang',    'label', $translator->_('Language'),
                'multiOptions', $localized->getLanguages(),
                'tab', $translator->_('Settings'));

        $this->setIfExists('gr2o_consent',    'label', $translator->_('Consent'),
                'multiOptions', $dbLookup->getUserConsents()
                );

        $this->setIfExists('gr2o_opened',
                'label', $translator->_('Opened'),
                'formatFunction', $translated->formatDateTime
                );

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param mixed $locale The locale for the settings
     * @return \Gems_Model_RespondentModel
     */
    public function applyEditSettings($locale = null)
    {
        $this->applyDetailSettings($locale);
        $this->copyKeys(); // The user can edit the keys.

        $translated = $this->util->getTranslated();
        $translator = $this->translate->getAdapter();
        $ucfirst    = new Zend_Filter_Callback('ucfirst');

        if ($this->hashSsn !== Gems_Model_RespondentModel::SSN_HIDE) {
            $this->set('grs_ssn', 'validator[]', $this->createUniqueValidator('grs_ssn'));
        }

        $this->setIfExists('gr2o_patient_nr',
                'size', 15,
                'minlength', 4,
                'validator', $this->createUniqueValidator(
                        array('gr2o_patient_nr', 'gr2o_id_organization'),
                        array('gr2o_id_user' => 'grs_id_user', 'gr2o_id_organization')
                        )
                );
        $this->set('grs_id_user');

        $this->set('grs_email',
                'size', 30,
                'validator', 'SimpleEmail');
        $this->addColumn('CASE WHEN grs_email IS NULL OR LENGTH(TRIM(grs_email)) = 0 THEN 1 ELSE 0 END', 'calc_email');
        $this->set('calc_email',
                'label', $translator->_('Respondent has no e-mail'),
                'elementClass', 'Checkbox',
                'order', $this->getOrder('grs_email') + 1,
                'validator', new Gems_Validate_OneOf(
                        $translator->_('Respondent has no e-mail'),
                        'grs_email',
                        $this->get('grs_email', 'label')
                        )
                );

        $this->setIfExists('grs_first_name', 'filter', $ucfirst);
        $this->setIfExists('grs_last_name',  'filter', $ucfirst, 'required', true);

        $this->setIfExists('grs_gender',
                'elementClass', 'Radio',
                'separator', '',
                'multiOptions', $translated->getGenders(),
                'tab', $translator->_('Medical data')
                );

        $this->setIfExists('grs_birthday',
                'jQueryParams', array('defaultDate' => '-30y', 'maxDate' => 0, 'yearRange' => 'c-130:c0'),
                'elementClass', 'Date',
                'validator', new MUtil_Validate_Date_DateBefore());

        $this->setIfExists('gr2o_treatment', 'size', 30);
        $this->setIfExists('gr2o_comments',  'elementClass', 'Textarea', 'rows', 4, 'cols', 60);

        $this->setIfExists('grs_address_1',
                'size',  40,
                'description', $translator->_('With housenumber'),
                'filter', $ucfirst
                );
        $this->setIfExists('grs_address_2', 'size', 40);
        $this->setIfExists('grs_city', 'filter', $ucfirst);
        $this->setIfExists('grs_phone_1', 'size', 15);
        $this->setIfExists('grs_phone_2', 'size', 15);
        $this->setIfExists('grs_phone_3', 'size', 15);

        $this->setIfExists('gr2o_opened', 'elementClass', 'Exhibitor');

        $this->setIfExists('gr2o_consent',
                'default', $this->util->getDefaultConsent(),
                'elementClass', 'Radio',
                'separator', '',
                'description', $translator->_('Has the respondent signed the informed consent letter?'),
                'required', true);

        return $this;
    }

    /**
     * Apply hash function for array_walk_recursive in _checkFilterUsed()
     *
     * @see _checkFilterUsed()
     *
     * @param string $filterValue
     * @param string $filterKey
     */
    public function applyHash(&$filterValue, $filterKey)
    {
        if ('grs_ssn' === $filterKey) {
            $filterValue = $this->project->getValueHash($filterValue);
        }
    }

    public function copyKeys($reset = false)
    {
        $keys = $this->_getKeysFor('gems__respondent2org');

        foreach ($keys as $key) {
            $this->addColumn('gems__respondent2org.' . $key, $this->getKeyCopyName($key));
        }

        return $this;
    }

    public function getRespondentTracksModel()
    {
        $model = new Gems_Model_JoinModel('surveys', 'gems__respondent2track');
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__respondent2org', array('gr2t_id_user' => 'gr2o_id_user'));

        return $model;
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The output to display
     */
    public function hideSSN($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            return str_repeat('*', 9);
        }
    }

    /**
     * True when the default filter can contain multiple organizations
     *
     * @return boolean
     */
    public function isMultiOrganization()
    {
        // return ($this->user->hasPrivilege('pr.respondent.multiorg') && (! $this->user->getCurrentOrganization()->canHaveRespondents()));
        return $this->user->hasPrivilege('pr.respondent.multiorg');
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function saveSSN($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            return $this->project->getValueHash($value);
        }
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function whenSSN($value, $isNew = false, $name = null, array $context = array())
    {
        return $value && ($value !== $this->hideSSN($value, $isNew, $name, $context));
    }
}

