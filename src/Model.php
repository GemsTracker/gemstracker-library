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

use DateTimeImmutable;
use Gems\Agenda\Agenda;
use Gems\Model\AppointmentModel;
use Gems\Model\CommLogModel;
use Gems\Model\CommMessengersModel;
use Gems\Model\CommTemplateModel;
use Gems\Model\ConditionModel;
use Gems\Model\EpisodeOfCareModel;
use Gems\Model\ExportDbaModel;
use Gems\Model\JoinModel;
use Gems\Model\LogModel;
use Gems\Model\MaskedModel;
use Gems\Model\OrganizationModel;
use Gems\Model\Respondent\RespondentModel;
use Gems\Model\RespondentRelationModel;
use Gems\Model\SiteModel;
use Gems\Model\StaffLogModel;
use Gems\Model\StaffModel;
use Gems\Model\SurveyCodeBookModel;
use Gems\Project\ProjectSettings;
use Gems\User\Mask\MaskRepository;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Sql;
use MUtil\Db\Expr\CurrentTimestamp;
use MUtil\Model\DatabaseModelAbstract;
use MUtil\Model\FolderModel;
use MUtil\Model\ModelAbstract;
use OpenRosa\Model\OpenRosaFormModel;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Transform\ModelTransformerInterface;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Expr;

/**
 * Central storage / access point for working with gems models.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Model
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
     * @var int Current user ID
     */
    protected static int $currentUserId = UserLoader::UNKNOWN_USER_ID;

    protected ProjectOverloader $overloader;

    /**
     * The length of a user id.
     *
     * @var int
     */
    protected int $userIdLen = 8;

    public function __construct(
        protected Adapter $db,
        protected MaskRepository $maskRepository,
        protected ProjectSettings $project,
        protected TranslatorInterface $translate,
        protected Translated $translatedUtil,
        ProjectOverloader $overloader
    ) {
        $this->overloader = $overloader->createSubFolderOverloader('Model');
    }

    protected function _createModel(string $modelClass, ...$args): DataReaderInterface
    {
        $model = $this->overloader->create($modelClass, ...$args);

        if ($model instanceof MaskedModel) {
            $model->setMaskRepository($this->maskRepository);
        }

        return $model;
    }

    /**
     * Add database translations to a model
     *
     * @param ModelAbstract $model
     */
    public function addDatabaseTranslations(ModelAbstract $model): void
    {
        if ($this->project->translateDatabaseFields()) {
            /**
             * @var ModelTransformerInterface $transformer
             */
            $transformer = $this->overloader->create('Transform\\TranslateDatabaseFields');
            $model->addTransformer($transformer);
        }
    }

    /**
     * Add database translation edit to model
     *
     * @param ModelAbstract $model
     */
    public function addDatabaseTranslationEditFields(ModelAbstract $model): void
    {
        if ($this->project->translateDatabaseFields()) {
            /**
             * @var ModelTransformerInterface $transformer
             */
            $transformer = $this->overloader->create('Transform\\TranslateFieldEditor');
            $model->addTransformer($transformer);
        }
    }

    /**
     * Link the model to the user_logins table.
     *
     * @param JoinModel $model
     * @param string $loginField Field that links to login name field.
     * @param string $organizationField Field that links to the organization field.
     */
    protected function addUserLogin(JoinModel $model, string $loginField, string $organizationField): void
    {
        $model->addTable(
                'gems__user_logins',
                array($loginField => 'gul_login', $organizationField => 'gul_id_organization'),
                'gul',
                DatabaseModelAbstract::SAVE_MODE_INSERT |
                DatabaseModelAbstract::SAVE_MODE_UPDATE |
                DatabaseModelAbstract::SAVE_MODE_DELETE
                );

        if ($model->has('gul_two_factor_key')) {
            $model->addColumn(
                new Zend_Db_Expr("CASE
                        WHEN gul_two_factor_key LIKE 'AuthenticatorTotp%' THEN 1
                        ELSE 0 END"),
                'has_authenticator_tfa'
            );
        }
    }

    /**
     * Load project specific application model or general \Gems model otherwise
     *
     * @return AppointmentModel
     */
    public function createAppointmentModel(): AppointmentModel
    {
        $agenda = $this->overloader->getContainer()->get(Agenda::class);
        return $this->_createModel('AppointmentModel', $agenda);
    }

    /**
     * Load project specific application model or general \Gems model otherwise
     *
     * @return ?EpisodeOfCareModel
     */
    public function createEpisodeOfCareModel(): EpisodeOfCareModel
    {
        $agenda = $this->overloader->getContainer()->get(Agenda::class);
        return $this->_createModel('EpisodeOfCareModel', $agenda);
    }

    /**
     * Create a \Gems project wide unique user id
     *
     * @see \Gems\Model\RespondentModel
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string|null $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return int
     */
    public function createGemsUserId(mixed $value, bool $isNew = false, ?string $name = null, array $context = []): int
    {
        if ($value) {
            return $value;
        }

        $now = new DateTimeImmutable();
        $sql = new Sql($this->db);

        do {
            $out = mt_rand(1, 9);
            for ($i = 1; $i < $this->userIdLen; $i++) {
                $out .= mt_rand(0, 9);
            }
            // Make it a number
            $out = intval($out);

            $values = [
                'gui_id_user' => $out,
                'gui_created' => $now->format('Y-m-d H:i:s'),
            ];
            $insert = $sql->insert('gems__user_ids')->values($values);
            $result = $sql->prepareStatementForSqlObject($insert)->execute();
            if ((!$result instanceof ResultInterface) || 0 === $result->getAffectedRows()) {
                $out = null;
            }
        } while (null === $out);

        return $out;
    }

    /**
     * Load project specific model or general \Gems model otherwise
     *
     * @return LogModel
     */
    public function createLogModel(): LogModel
    {
        /**
         * @var LogModel
         */
        return $this->_createModel('LogModel');
    }

    /**
     * Load project specific model or general \Gems model otherwise
     *
     * @return RespondentModel
     * @deprecated
     */
    public function createRespondentModel(): RespondentModel
    {
        /**
         * @var RespondentModel $model
         */
        $model = $this->_createModel(RespondentModel::class);

//        $this->setAsGemsUserId($model, 'grs_id_user');

        return $model;
    }

    /**
     * Load the comm log model
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @return CommLogModel
     */
    public function getCommLogModel(bool $detailed): CommLogModel
    {
        /**
         * @var CommLogModel $model
         */
        $model = $this->_createModel('CommLogModel');
        $model->applySetting($detailed);

        return $model;
    }

    /**
     * Load the Comm Methods model
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @return CommMessengersModel
     */
    public function getCommMessengersModel(bool $detailed): CommMessengersModel
    {
        /**
         * @var CommMessengersModel $model
         */
        $model = $this->_createModel('CommMessengersModel');
        $model->applySetting($detailed);

        return $model;
    }

    /**
     * Load the commtemplate model
     *
     * @return CommtemplateModel
     */
    public function getCommtemplateModel(): CommtemplateModel
    {
        /**
         * @var CommtemplateModel $model
         */
        $model = $this->_createModel('CommtemplateModel');

        return $model;
    }

    /**
     * Load the condition model
     *
     * @return ConditionModel
     */
    public function getConditionModel(): ConditionModel
    {
        /**
         * @var ConditionModel $model
         */
        $model = $this->_createModel('ConditionModel');

        return $model;
    }

    /**
     * Load the export Dba Model
     *
     * @return ExportDbaModel
     */
    public function getExportDbaModel(Zend_Db_Adapter_Abstract $db, array $directories): ExportDbaModel
    {
        /**
         * @var ExportDbaModel $model
         */
        $model = $this->_createModel('ExportDbaModel', [$db, $directories]);

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
    public function getFileModel(string $dir, bool $detailed = true, mixed $extensionsOrMask = null, bool $recursive = false, bool $followSymlinks = false): FolderModel
    {
        $model = new FolderModel($dir, $extensionsOrMask, $recursive, $followSymlinks);

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
                    'dateFormat', $this->translatedUtil->dateTimeFormatString,
                    'elementClass', 'Exhibitor');

        return $model;
    }

    /**
     * Returns the OpenRosaFormModel
     *
     * It is special since it can show how many responses each table has
     *
     * @return OpenRosaFormModel
     */
    public function getOpenRosaFormModel(): OpenRosaFormModel
    {
        /**
         * @var OpenRosaFormModel $model
         */
        $model = $this->_createModel('OpenRosaFormModel');

        return $model;
    }

    /**
     * Load the organization model
     *
     * @param array|mixed $styles
     * @return OrganizationModel
     */
    public function getOrganizationModel(array $styles = []): OrganizationModel
    {
        /**
         * @var OrganizationModel $model
         */
        $model = $this->_createModel('OrganizationModel', $styles);

        return $model;
    }

    /**
     * Load project specific respondent model or general \Gems model otherwise
     *
     * @param boolean $detail When true more information needed for individual item display is added to the model.
     * @return RespondentModel
     * @deprecated
     */
    public function getRespondentModel(bool $detailed): RespondentModel
    {
        $model      = $this->createRespondentModel();

        if ($detailed) {
            $model->applyStringAction('edit', true);
        } else {
            $model->applyStringAction('index', false);
        }

        return $model;
    }

    /**
     * Get the respondent relation model
     *
     * @return RespondentRelationModel
     */
    public function getRespondentRelationModel(): RespondentRelationModel
    {
        /**
         * @var RespondentRelationModel
         */
        return $this->_createModel('RespondentRelationModel');
    }

    /**
     * Load the organization model
     *
     * @return SiteModel
     */
    public function getSiteModel(): SiteModel
    {
        /**
         * @var SiteModel
         */
        return $this->_createModel('SiteModel');
    }

    /**
     * Load the staffmodel
     *
     * @param boolean $addLogin Add the login tables to the model
     * @return \Gems\Model\StaffModel
     */
    public function getStaffModel(bool $addLogin = true): StaffModel
    {
        /**
         * @var StaffModel $model
         */
        $model = $this->_createModel('StaffModel');

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
    public function getStaffLogModel(bool $detailed): StaffLogModel
    {
        /**
         * @var StaffLogModel $model
         */
        $model = $this->_createModel('StaffLogModel', true);
        if ($detailed) {
            $model->applyDetailSettings();
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Get the survey codebook Model
     *
     * @param int $surveyId
     * @return SurveyCodeBookModel
     */
    public function getSurveyCodeBookModel(int $surveyId): SurveyCodeBookModel
    {
        /**
         * @var SurveyCodeBookModel
         */
        return $this->_createModel('SurveyCodeBookModel', true, [$surveyId]);
    }

    /**
     * Set a field in this model as a gems unique user id
     *
     * @param DatabaseModelAbstract $model
     * @param string $idField Field that uses global id.
     */
    public function setAsGemsUserId(DatabaseModelAbstract $model, string $idField): void
    {
        // Make sure field is added to save when not there
        $model->setAutoSave($idField);

        // Make sure the fields get a userid when empty
        $model->setOnSave($idField, array($this, 'createGemsUserId'));
    }

    /**
     * Function that automatically fills changed, changed_by, created and created_by fields with a certain prefix.
     *
     * @param DatabaseModelAbstract $model
     * @param string $prefix Three letter code
     * @param int|null $userid \Gems user id
     */
    public static function setChangeFieldsByPrefix(DatabaseModelAbstract $model, string $prefix, int $userid = null): void
    {
        $changed_field    = $prefix . '_changed';
        $changed_by_field = $prefix . '_changed_by';
        $created_field    = $prefix . '_created';
        $created_by_field = $prefix . '_created_by';

        foreach (array($changed_field, $changed_by_field, $created_field, $created_by_field) as $field) {
            $model->set($field, 'elementClass', 'None');
        }

        $model->setOnSave($changed_field, new CurrentTimestamp());
        $model->setSaveOnChange($changed_field);
        $model->setOnSave($created_field, new CurrentTimestamp());
        $model->setSaveWhenNew($created_field);

        if (! $userid) {
            $userid = self::$currentUserId;
        }

        $model->setOnSave($changed_by_field, $userid);
        $model->setSaveOnChange($changed_by_field);
        $model->setOnSave($created_by_field, $userid);
        $model->setSaveWhenNew($created_by_field);
    }

    /**
     * Set the current User ID
     *
     * @param int $userId
     */
    public static function setCurrentUserId(int $userId): void
    {
        self::$currentUserId = $userId;
    }
}
