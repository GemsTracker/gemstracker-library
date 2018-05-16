<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
class Gems_Model extends \Gems_Loader_TargetLoaderAbstract
{
    const ID_TYPE = 'id_type';

    /**
     * Request key for appointments
     */
    const APPOINTMENT_ID = 'aid';

    /**
     * Request key for episodes of care
     */
    const EPISODE_ID = 'eid';

    /**
     * Request key for track fields
     */
    const FIELD_ID = 'fid';

    /**
     * Request key for log items
     */
    const LOG_ITEM_ID = 'li';

    /**
     * Request key for respondent tracks
     */
    const RESPONDENT_TRACK = 'rt';

    /**
     * Request key for rounds
     */
    const ROUND_ID = 'rid';

    /**
     * Request keys for surveys
     */
    const SURVEY_ID = 'si';

    /**
     * Request key for tracks (not respondent tracks, tracks!)
     */
    const TRACK_ID = 'tr';

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Model';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Field name in respondent model containing the login id.
     *
     * @var string
     */
    public $respondentLoginIdField  = 'gr2o_patient_nr';

    /**
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * The length of a user id.
     *
     * @var int
     */
    protected $userIdLen = 8;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Link the model to the user_logins table.
     *
     * @param \Gems_Model_JoinModel $model
     * @param string $loginField Field that links to login name field.
     * @param string $organizationField Field that links to the organization field.
     */
    protected function addUserLogin(\Gems_Model_JoinModel $model, $loginField, $organizationField)
    {
        $model->addTable(
                'gems__user_logins',
                array($loginField => 'gul_login', $organizationField => 'gul_id_organization'),
                'gul',
                \MUtil_Model_DatabaseModelAbstract::SAVE_MODE_INSERT |
                \MUtil_Model_DatabaseModelAbstract::SAVE_MODE_UPDATE |
                \MUtil_Model_DatabaseModelAbstract::SAVE_MODE_DELETE
                );
    }

    /**
     * Link the model to the user_passwords table.
     *
     * @param \Gems_Model_JoinModel $model
     * @deprecated since version 1.5.4
     */
    public static function addUserPassword(\Gems_Model_JoinModel $model)
    {
        if (! $model->hasAlias('gems__user_passwords')) {
            $model->addLeftTable('gems__user_passwords', array('gul_id_user' => 'gup_id_user'), 'gup');
        }
    }

    /**
     * Load project specific application model or general Gems model otherwise
     *
     * @return \Gems_Model_AppointmentModel
     */
    public function createAppointmentModel()
    {
        return $this->_loadClass('AppointmentModel', true);
    }

    /**
     * Load project specific application model or general Gems model otherwise
     *
     * @return \Gems_Model_AppointmentModel
     */
    public function createEpisodeOfCareModel()
    {
        return $this->_loadClass('EpisodeOfCareModel', true);
    }

    /**
     * Create a Gems project wide unique user id
     *
     * @see \Gems_Model_RespondentModel
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return int
     */
    public function createGemsUserId($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            return $value;
        }

        $creationTime = new \MUtil_Db_Expr_CurrentTimestamp();

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
            } catch (\Zend_Db_Exception $e) {
                $out = null;
            }
        } while (null === $out);

        return $out;
    }

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return \Gems\Model\LogModel
     */
    public function createLogModel()
    {
        return $this->_loadClass('LogModel', true);
    }

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return \Gems_Model_RespondentModel
     */
    public function createRespondentModel()
    {
        $model = $this->_loadClass('RespondentModel', true);

        $this->setAsGemsUserId($model, 'grs_id_user');

        return $model;
    }

    /**
     * Load the comm log model
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @return \Gems\Model\CommLogModel
     */
    public function getCommLogModel($detailed)
    {
        $model = $this->_loadClass('CommLogModel', true);
        $model->applySetting($detailed);

        return $model;
    }

    /**
     * Load the commtemplate model
     *
     * @return \Gems_Model_CommtemplateModel
     */
    public function getCommtemplateModel()
    {
        $model = $this->_loadClass('CommtemplateModel', true);

        return $model;
    }

    /**
     * Returns the OpenRosaFormModel
     *
     * It is special since it can show how many responses each table has
     *
     * @return \OpenRosa_Model_OpenRosaFormModel
     */
    public function getOpenRosaFormModel()
    {
        $model = $this->_loadClass('OpenRosaFormModel', true);

        return $model;
    }

    /**
     * Load the organization model
     *
     * @param array|mixed $styles
     * @return \Gems_Model_OrganizationModel
     */
    public function getOrganizationModel($styles = array())
    {
        $model = $this->_loadClass('OrganizationModel', true, array($styles));

        return $model;
    }

    /**
     * Load project specific respondent model or general Gems model otherwise
     *
     * @param boolean $detail When true more information needed for individual item display is added to the model.
     * @return \Gems_Model_RespondentModel
     */
    public function getRespondentModel($detailed)
    {
        static $isDetailed;
        static $model;

        if ($model && ($isDetailed === $detailed)) {
            return $model;
        }

        $isDetailed = $detailed;
        $model      = $this->createRespondentModel();

        if ($detailed) {
            $model->applyDetailSettings();
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Get the respondent relation model
     *
     * @return \Gems_Model_RespondentRelationModel
     */
    public function getRespondentRelationModel()
    {
        return $this->_loadClass('RespondentRelationModel', true);
    }

    /**
     * Load the staffmodel
     *
     * @param boolean $addLogin Add the login tables to the model
     * @return \Gems_Model_StaffModel
     */
    public function getStaffModel($addLogin = true)
    {
        $model = $this->_loadClass('StaffModel', true);

        if ($addLogin) {
            $this->addUserLogin($model, 'gsf_login', 'gsf_id_organization');
        }
        $this->setAsGemsUserId($model, 'gsf_id_user');

        return $model;
    }

    /**
     * Set a field in this model as a gems unique user id
     *
     * @param \MUtil_Model_DatabaseModelAbstract $model
     * @param string $idField Field that uses global id.
     */
    public function setAsGemsUserId(\MUtil_Model_DatabaseModelAbstract $model, $idField)
    {
        // Make sure field is added to save when not there
        $model->setAutoSave($idField);

        // Make sure the fields get a userid when empty
        $model->setOnSave($idField, array($this, 'createGemsUserId'));
    }

    /**
     * Function that automatically fills changed, changed_by, created and created_by fields with a certain prefix.
     *
     * @param \MUtil_Model_DatabaseModelAbstract $model
     * @param string $prefix Three letter code
     * @param int $userid Gems user id
     */
    public static function setChangeFieldsByPrefix(\MUtil_Model_DatabaseModelAbstract $model, $prefix, $userid = null)
    {
        $changed_field    = $prefix . '_changed';
        $changed_by_field = $prefix . '_changed_by';
        $created_field    = $prefix . '_created';
        $created_by_field = $prefix . '_created_by';

        foreach (array($changed_field, $changed_by_field, $created_field, $created_by_field) as $field) {
            $model->set($field, 'elementClass', 'none');
        }

        $model->setOnSave($changed_field, new \MUtil_Db_Expr_CurrentTimestamp());
        $model->setSaveOnChange($changed_field);
        $model->setOnSave($created_field, new \MUtil_Db_Expr_CurrentTimestamp());
        $model->setSaveWhenNew($created_field);

        if (! $userid) {
            $currentUser = \GemsEscort::getInstance()->currentUser;

            if ($currentUser instanceof Gems_User_User) {   // During some unit tests this will be null
                $userid = $currentUser->getUserId();
            }

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
