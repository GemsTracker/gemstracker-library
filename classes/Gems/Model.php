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
 * Central storage / access point for working with gems models.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model extends Gems_Loader_TargetLoaderAbstract
{
    const ID_TYPE = 'id_type';
    const RESPONDENT_TRACK = 'rt';
    const ROUND_ID = 'rid';
    const SURVEY_ID = 'si';
    const TRACK_ID = 'tr';

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Model';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Field name in respondent model containing the login id.
     *
     * @var string
     */
    public $respondentLoginIdField  = 'gr2o_patient_nr';

    /**
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * The length of a user id.
     *
     * @var int
     */
    protected $userIdLen = 8;

    /**
     * @var Gems_Util
     */
    protected $util;

    /**
     * Link the model to the user_logins table.
     *
     * @param Gems_Model_JoinModel $model
     * @param string $loginField Field that links to login name field.
     * @param string $organizationField Field that links to the organization field.
     */
    protected function addUserLogin(Gems_Model_JoinModel $model, $loginField, $organizationField)
    {
        $model->addTable(
                'gems__user_logins',
                array($loginField => 'gul_login', $organizationField => 'gul_id_organization'),
                'gul',
                MUtil_Model_DatabaseModelAbstract::SAVE_MODE_UPDATE |
                    MUtil_Model_DatabaseModelAbstract::SAVE_MODE_DELETE
                );
    }

    /**
     * Link the model to the user_passwords table.
     *
     * @param Gems_Model_JoinModel $model
     * @deprecated since version 1.5.4
     */
    public static function addUserPassword(Gems_Model_JoinModel $model)
    {
        $model->addLeftTable('gems__user_passwords', array('gul_id_user' => 'gup_id_user'), 'gup');
    }

    /**
     * Create a Gems project wide unique user id
     *
     * @see Gems_Model_RespondentModel
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return int
     */
    public function createGemsUserId($value, $isNew = false, $name = null, array $context = array())
    {
        if ($isNew || (null === $value)) {
            $creationTime = new MUtil_Db_Expr_CurrentTimestamp();

            do {
                $out = mt_rand(1, 9);
                for ($i = 1; $i < $this->userIdLen; $i++) {
                    $out .= mt_rand(0, 9);
                }
                // Make it a number
                $out = intval($out);

                try {
                    if (0 === $this->db->insert('gems__user_ids', array('gui_id_user' => $out, 'gui_created' => $creationTime))) {
                        $out = null;
                    }
                } catch (Zend_Db_Exception $e) {
                    $out = null;
                }
            } while (null === $out);

            return $out;
        }

        return $value;
    }

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return Gems_Model_RespondentModel
     */
    public function createRespondentModel()
    {
        $model = $this->_loadClass('RespondentModel', true);

        // $this->addUserLogin($model, $this->respondentLoginIdField, 'gr2o_id_organization');
        $this->setAsGemsUserId($model, 'grs_id_user');

        return $model;
    }
    
    /**
     * Returns the OpenRosaFormModel
     *
     * It is special since it can show how many responses each table has
     *
     * @return OpenRosa_Model_OpenRosaFormModel
     */
    public function getOpenRosaFormModel()
    {
        $model = $this->_loadClass('OpenRosaFormModel', true);

        return $model;
    }

    /**
     * Load the organization model
     *
     * @return Gems_Model_OrganizationModel
     */
    public function getOrganizationModel()
    {
        $model = $this->_loadClass('OrganizationModel', true);

        return $model;
    }

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @param boolean $detail When true more information needed for individual item display is added to the model.
     * @return Gems_Model_RespondentModel
     */
    public function getRespondentModel($detailed)
    {
        static $model;
        static $is_detailed;

        if ($model && ($is_detailed === $detailed)) {
            return $model;
        }

        $model      = $this->createRespondentModel();

        $translated = $this->util->getTranslated();

        $model->setIfExists('gr2o_patient_nr',    'label', $this->translate->_('Respondent nr'));
        if ((! $detailed) && $model->isMultiOrganization()) {
            $model->addTable('gems__organizations', array('gr2o_id_organization' => 'gor_id_organization'));
            $model->setIfExists('gor_name', 'label', $this->translate->_('Organization'));
        }
        $model->setIfExists('gr2o_opened',        'label', $this->translate->_('Opened'), 'formatFunction', $translated->formatDateTime);
        $model->setIfExists('gr2o_consent',       'label', $this->translate->_('Consent'), 'multiOptions', MUtil_Lazy::call($this->util->getDbLookup()->getUserConsents), 'default', $this->util->getDefaultConsent());

        $model->setIfExists('grs_email',          'label', $this->translate->_('E-Mail'));

        if ($detailed) {
            $model->copyKeys(); // The user can edit the keys.

            $model->setIfExists('grs_gender',         'label', $this->translate->_('Gender'), 'multiOptions', $translated->getGenderHello());
            $model->setIfExists('grs_first_name',     'label', $this->translate->_('First name'));
            $model->setIfExists('grs_surname_prefix', 'label', $this->translate->_('Surname prefix'));
            $model->setIfExists('grs_last_name',      'label', $this->translate->_('Last name'));
        }
        $model->set('name',                       'label', $this->translate->_('Name'),
            'column_expression', "CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, ''))",
            'fieldlist', array('grs_last_name', 'grs_first_name', 'grs_surname_prefix'));

        $model->setIfExists('grs_address_1',      'label', $this->translate->_('Street'));
        $model->setIfExists('grs_zipcode',        'label', $this->translate->_('Zipcode'));
        $model->setIfExists('grs_city',           'label', $this->translate->_('City'));

        $model->setIfExists('grs_phone_1',        'label', $this->translate->_('Phone'));

        $model->setIfExists('grs_birthday',       'label', $this->translate->_('Birthday'), 'dateFormat', Zend_Date::DATE_MEDIUM);

        $model->setIfExists('grs_iso_lang',       'default', 'nl');

        return $model;
    }

    /**
     * Load the staffmodel
     *
     * @return Gems_Model_StaffModel
     */
    public function getStaffModel()
    {
        $model = $this->_loadClass('StaffModel', true, array($this->loader));

        $this->addUserLogin($model, 'gsf_login', 'gsf_id_organization');
        $this->setAsGemsUserId($model, 'gsf_id_user');

        return $model;
    }

    /**
     * Set a field in this model as a gems unique user id
     *
     * @param MUtil_Model_DatabaseModelAbstract $model
     * @param string $idField Field that uses global id.
     */
    public function setAsGemsUserId(MUtil_Model_DatabaseModelAbstract $model, $idField)
    {
        // Make sure field is added to save when not there
        $model->setAutoSave($idField);

        // Make sure the fields get a userid when empty
        $model->setOnSave($idField, array($this, 'createGemsUserId'));
    }

    /**
     * Function that automatically fills changed, changed_by, created and created_by fields with a certain prefix.
     *
     * @param MUtil_Model_DatabaseModelAbstract $model
     * @param string $prefix Three letter code
     * @param int $userid Gems user id
     */
    public static function setChangeFieldsByPrefix(MUtil_Model_DatabaseModelAbstract $model, $prefix, $userid = null)
    {
        $changed_field    = $prefix . '_changed';
        $changed_by_field = $prefix . '_changed_by';
        $created_field    = $prefix . '_created';
        $created_by_field = $prefix . '_created_by';

        $model->setOnSave($changed_field, new MUtil_Db_Expr_CurrentTimestamp());
        $model->setSaveOnChange($changed_field);
        $model->setOnSave($created_field, new MUtil_Db_Expr_CurrentTimestamp());
        $model->setSaveWhenNew($created_field);

        if (! $userid) {
            $userid = GemsEscort::getInstance()->session->user_id;
            if (! $userid) {
                $userid = 1;
            }
        }
        if ($userid) {
            $model->setOnSave($changed_by_field, $userid);
            $model->setSaveOnChange($changed_by_field);
            $model->setOnSave($created_by_field, $userid);
            $model->setSaveWhenNew($created_by_field);
        }
    }
}
