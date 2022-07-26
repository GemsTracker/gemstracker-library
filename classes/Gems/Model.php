<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 * Central storage / access point for working with gems models.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Model extends \Gems\Loader\TargetLoaderAbstract
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
     * Allows sub classes of \Gems\Loader\LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Model';

    /**
     * @var int Current user ID
     */
    protected static $currentUserId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

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
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Add database translations to a model
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    public function addDatabaseTranslations(\MUtil\Model\ModelAbstract $model)
    {
        if ($this->project->translateDatabaseFields()) {
            $transformer = $this->_loadClass('Transform\\TranslateDatabaseFields', true);
            $model->addTransformer($transformer);
        }
    }

    /**
     * Add database translation edit to model
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    public function addDatabaseTranslationEditFields(\MUtil\Model\ModelAbstract $model)
    {
        if ($this->project->translateDatabaseFields()) {
            $transformer = $this->_loadClass('Transform\\TranslateFieldEditor', true);
            $model->addTransformer($transformer);
        }
    }

    /**
     * Link the model to the user_logins table.
     *
     * @param \Gems\Model\JoinModel $model
     * @param string $loginField Field that links to login name field.
     * @param string $organizationField Field that links to the organization field.
     */
    protected function addUserLogin(\Gems\Model\JoinModel $model, $loginField, $organizationField)
    {
        $model->addTable(
                'gems__user_logins',
                array($loginField => 'gul_login', $organizationField => 'gul_id_organization'),
                'gul',
                \MUtil\Model\DatabaseModelAbstract::SAVE_MODE_INSERT |
                \MUtil\Model\DatabaseModelAbstract::SAVE_MODE_UPDATE |
                \MUtil\Model\DatabaseModelAbstract::SAVE_MODE_DELETE
                );

        if ($model->has('gul_enable_2factor') && $model->has('gul_two_factor_key')) {
            $model->addColumn(
                    new \Zend_Db_Expr("CASE
                        WHEN gul_enable_2factor IS NULL THEN -1
                        WHEN gul_enable_2factor = 1 AND gul_two_factor_key IS NULL THEN 1
                        WHEN gul_enable_2factor = 1 AND gul_two_factor_key IS NOT NULL THEN 2
                        ELSE 0 END"),
                    'has_2factor'
                    );
        }
    }

    /**
     * Link the model to the user_passwords table.
     *
     * @param \Gems\Model\JoinModel $model
     * @deprecated since version 1.5.4
     */
    public static function addUserPassword(\Gems\Model\JoinModel $model)
    {
        if (! $model->hasAlias('gems__user_passwords')) {
            $model->addLeftTable('gems__user_passwords', array('gul_id_user' => 'gup_id_user'), 'gup');
        }
    }

    /**
     * Load project specific application model or general \Gems model otherwise
     *
     * @return \Gems\Model\AppointmentModel
     */
    public function createAppointmentModel()
    {
        return $this->_loadClass('AppointmentModel', true);
    }

    /**
     * Load project specific application model or general \Gems model otherwise
     *
     * @return \Gems\Model\AppointmentModel
     */
    public function createEpisodeOfCareModel()
    {
        return $this->_loadClass('EpisodeOfCareModel', true);
    }

    /**
     * Create a \Gems project wide unique user id
     *
     * @see \Gems\Model\RespondentModel
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

        $creationTime = new \MUtil\Db\Expr\CurrentTimestamp();

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
     * Load project specific model or general \Gems model otherwise
     *
     * @return \Gems\Model\LogModel
     */
    public function createLogModel()
    {
        return $this->_loadClass('LogModel', true);
    }

    /**
     * Load project specific model or general \Gems model otherwise
     *
     * @return \Gems\Model\RespondentModel
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
     * Load the Comm Methods model
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @return \Gems\Model\CommMessengersModel
     */
    public function getCommMessengersModel($detailed)
    {
        $model = $this->_loadClass('CommMessengersModel', true);
        $model->applySetting($detailed);

        return $model;
    }

    /**
     * Load the commtemplate model
     *
     * @return \Gems\Model\CommtemplateModel
     */
    public function getCommtemplateModel()
    {
        $model = $this->_loadClass('CommtemplateModel', true);

        return $model;
    }

    /**
     * Load the condition model
     *
     * @param array|mixed $styles
     * @return \Gems\Model\ConditionModel
     */
    public function getConditionModel()
    {
        $model = $this->_loadClass('ConditionModel', true);

        return $model;
    }

    /**
     * Load the export Dba Model
     *
     * @return \Gems\Model\ExportDbaModel
     */
    public function getExportDbaModel(\Zend_Db_Adapter_Abstract $db, array $directories)
    {
        $model = $this->_loadClass('ExportDbaModel', true, [$db, $directories]);

        return $model;
    }

    /**
     * @param string  $dir The (start) directory
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param mixed   $extensionsOrMask An optional array of extensions or a regex file mask, use of / for directory separator required
     * @param boolean $recursive When true the directory is searched recursively
     * @param boolean $followSymlinks When true symlinks are folloed
     * @return \MUtil\Model\FolderModel
     */
    public function getFileModel($dir, $detailed = true, $extensionsOrMask = null, $recursive = false, $followSymlinks = false)
    {
        $model = new \MUtil\Model\FolderModel($dir, $extensionsOrMask, $recursive, $followSymlinks);

        if ($recursive) {
            $model->set('relpath',  'label', $this->translate->_('File (local)'),
                        'maxlength', 255,
                        'size', 40,
                        'validators', array('File_Path', 'File_IsRelativePath')
            );
            $model->set('filename', 'elementClass', 'Exhibitor');
        }
        if ($detailed || (! $recursive)) {
            $model->set('filename',  'label', $this->translate->_('Filename'), 'size', 30, 'maxlength', 255);
        }
        if ($detailed) {
            $model->set('path',      'label', $this->translate->_('Path'), 'elementClass', 'Exhibitor');
            $model->set('fullpath',  'label', $this->translate->_('Full name'), 'elementClass', 'Exhibitor');
            $model->set('extension', 'label', $this->translate->_('Type'), 'elementClass', 'Exhibitor');
            $model->set('content',   'label', $this->translate->_('Content'),
                        'formatFunction', array(\MUtil\Html::create(), 'pre'),
                        'elementClass', 'TextArea');
        }
        $model->set('size',      'label', $this->translate->_('Size'),
                    'formatFunction', array('\\MUtil\\File', 'getByteSized'),
                    'elementClass', 'Exhibitor');
        $model->set('changed',   'label', $this->translate->_('Changed on'),
                    'dateFormat', $this->util->getTranslated()->dateTimeFormatString,
                    'elementClass', 'Exhibitor');

        return $model;
    }

    /**
     * Returns the OpenRosaFormModel
     *
     * It is special since it can show how many responses each table has
     *
     * @return \OpenRosa\Model\OpenRosaFormModel
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
     * @return \Gems\Model\OrganizationModel
     */
    public function getOrganizationModel($styles = array())
    {
        $model = $this->_loadClass('OrganizationModel', true, array($styles));

        return $model;
    }

    /**
     * Load project specific respondent model or general \Gems model otherwise
     *
     * @param boolean $detail When true more information needed for individual item display is added to the model.
     * @return \Gems\Model\RespondentModel
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
     * @return \Gems\Model\RespondentRelationModel
     */
    public function getRespondentRelationModel()
    {
        return $this->_loadClass('RespondentRelationModel', true);
    }

    /**
     * Load the organization model
     *
     * @return \Gems\Model\SiteModel
     */
    public function getSiteModel()
    {
        return $this->_loadClass('SiteModel', true);
    }

    /**
     * Load the staffmodel
     *
     * @param boolean $addLogin Add the login tables to the model
     * @return \Gems\Model\StaffModel
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
     * Get the staff log model
     *
     * @param $detailed
     * @return \Gems\Model\StaffLogModel
     */
    public function getStaffLogModel($detailed)
    {
        $model = $this->_loadClass('StaffLogModel', true);
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
     * @return \Gems\Model\SurveyMaintenanceModel
     */
    public function getSurveyMaintenanceModel()
    {
        return $this->_loadClass('SurveyMaintenanceModel', true);
    }

    /**
     * Get the survey codebook Model
     *
     * @param $surveyId
     * @return \Gems\Model\SurveyCodeBookModel
     */
    public function getSurveyCodeBookModel($surveyId)
    {
        return $this->_loadClass('SurveyCodeBookModel', true, [$surveyId]);
    }

    /**
     * Set a field in this model as a gems unique user id
     *
     * @param \MUtil\Model\DatabaseModelAbstract $model
     * @param string $idField Field that uses global id.
     */
    public function setAsGemsUserId(\MUtil\Model\DatabaseModelAbstract $model, $idField)
    {
        // Make sure field is added to save when not there
        $model->setAutoSave($idField);

        // Make sure the fields get a userid when empty
        $model->setOnSave($idField, array($this, 'createGemsUserId'));
    }

    /**
     * Function that automatically fills changed, changed_by, created and created_by fields with a certain prefix.
     *
     * @param \MUtil\Model\DatabaseModelAbstract $model
     * @param string $prefix Three letter code
     * @param int $userid \Gems user id
     */
    public static function setChangeFieldsByPrefix(\MUtil\Model\DatabaseModelAbstract $model, $prefix, $userid = null)
    {
        $changed_field    = $prefix . '_changed';
        $changed_by_field = $prefix . '_changed_by';
        $created_field    = $prefix . '_created';
        $created_by_field = $prefix . '_created_by';

        foreach (array($changed_field, $changed_by_field, $created_field, $created_by_field) as $field) {
            $model->set($field, 'elementClass', 'none');
        }

        $model->setOnSave($changed_field, new \MUtil\Db\Expr\CurrentTimestamp());
        $model->setSaveOnChange($changed_field);
        $model->setOnSave($created_field, new \MUtil\Db\Expr\CurrentTimestamp());
        $model->setSaveWhenNew($created_field);

        if (! $userid && self::$currentUserId) {
            $userid = self::$currentUserId;
        }

        if (! $userid) {
            $escort = \Gems\Escort::getInstance();

            if ($escort) {
                $currentUser = $escort->currentUser;

                if ($currentUser instanceof \Gems\User\User) {   // During some unit tests this will be null
                    $userid = $currentUser->getUserId();
                }

                if (!$userid) {
                    $userid = 1;
                }
            }
        }
        if ($userid) {
            $model->setOnSave($changed_by_field, $userid);
            $model->setSaveOnChange($changed_by_field);
            $model->setOnSave($created_by_field, $userid);
            $model->setSaveWhenNew($created_by_field);
        }
    }

    /**
     * Set the current User ID
     *
     * @param $userId
     */
    public static function setCurrentUserId($userId)
    {
        self::$currentUserId = $userId;
    }
}
