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
 * @version    $Id: Model.php 345 2011-07-28 08:39:24Z 175780 $
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
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * @var Gems_Util
     */
    protected $util;

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return Gems_Model_RespondentModel
     */
    public function createRespondentModel()
    {
        return $this->_loadClass('RespondentModel', true);
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
        $model->setIfExists('gr2o_opened',        'label', $this->translate->_('Opened'), 'formatFunction', $translated->formatDateTime);
        $model->setIfExists('gr2o_consent',       'label', $this->translate->_('Consent'), 'multiOptions', MUtil_Lazy::call($this->util->getDbLookup()->getUserConsents));

        $model->setIfExists('grs_email',          'label', $this->translate->_('E-Mail'));

        if ($detailed) {
            $model->copyKeys(); // The user can edit the keys.

            $model->setIfExists('grs_gender',         'label', $this->translate->_('Gender'), 'multiOptions', $translated->getGenderHello());
            $model->setIfExists('grs_first_name',     'label', $this->translate->_('First name'));
            $model->setIfExists('grs_surname_prefix', 'label', $this->translate->_('Surname prefix'));
            $model->setIfExists('grs_last_name',      'label', $this->translate->_('Last name'));
        }
        $model->set('name',                       'label', $this->translate->_('Name'),
            'column_expression', "CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, ''))");

        $model->setIfExists('grs_address_1',      'label', $this->translate->_('Street'));
        $model->setIfExists('grs_zipcode',        'label', $this->translate->_('Zipcode'));
        $model->setIfExists('grs_city',           'label', $this->translate->_('City'));

        $model->setIfExists('grs_phone_1',        'label', $this->translate->_('Phone'));

        $model->setIfExists('grs_birthday',       'label', $this->translate->_('Birthday'), 'dateFormat', Zend_Date::DATE_MEDIUM);

        $model->setIfExists('grs_iso_lang',       'default', 'nl');

        return $model;
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

        $model->setOnSave($changed_field, new Zend_Db_Expr('CURRENT_TIMESTAMP'));
        $model->setSaveOnChange($changed_field);
        $model->setOnSave($created_field, new Zend_Db_Expr('CURRENT_TIMESTAMP'));
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
