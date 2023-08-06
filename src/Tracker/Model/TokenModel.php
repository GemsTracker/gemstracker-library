<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Model;

use Gems\Model\GemsJoinModel;
use Gems\Model\MaskedModelTrait;
use Gems\Model\Type\TokenValidFromType;
use Gems\Model\Type\TokenValidUntilType;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\TokenRepository;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\AbstractDateType;
use Zalt\Validator\Model\AfterDateModelValidator;

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @since      Class available since version 1.0
 */
class TokenModel extends GemsJoinModel
{
    use MaskedModelTrait;

    public static string $modelName = 'gems__tokens';

    /**
     * @var bool Temporary switch to enable / disable use of TokenModel
     */
    public static $useTokenModel = false;

    public function __construct(
        MetaModelInterface $metaModel,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
        protected OrganizationRepository $organizationRepository,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct($metaModel, $sqlRunner, $translate);

        $this->maskRepository = $maskRepository;

        $this->startJoin(self::$modelName, true);
        $metaModel->setKeys($this->getKeysForTable(self::$modelName));

        $this->addTable(    'gems__tracks',               ['gto_id_track' => 'gtr_id_track']);
        $this->addTable(    'gems__surveys',              ['gto_id_survey' => 'gsu_id_survey']);
        $this->addTable(    'gems__groups',               ['gsu_id_primary_group' => 'ggp_id_group']);
        $this->addTable(    'gems__respondents',          ['gto_id_respondent' => 'grs_id_user']);
        $this->addTable(    'gems__respondent2org',       ['gto_id_organization' => 'gr2o_id_organization', 'gto_id_respondent' => 'gr2o_id_user']);
        $this->addTable(    'gems__respondent2track',     ['gto_id_respondent_track' => 'gr2t_id_respondent_track']);
        $this->addTable(    'gems__organizations',        ['gto_id_organization' => 'gor_id_organization']);
        $this->addTable(    'gems__reception_codes',      ['gto_reception_code' => 'grc_id_reception_code']);
        $this->addTable(    'gems__rounds',               ['gto_id_round' => 'gro_id_round']);
        $this->addLeftTable('gems__staff',                ['gto_created_by' => 'gems__staff.gsf_id_user']);
        $this->addLeftTable('gems__track_fields',         ['gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"']);       // Add relation fields
        $this->addLeftTable('gems__respondent_relations', ['gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent']); // Add relation

        $this->addColumn(
            "CASE WHEN CHAR_LENGTH(gsu_survey_name) > 30 THEN CONCAT(SUBSTR(gsu_survey_name, 1, 28), '...') ELSE gsu_survey_name END",
            'survey_short',
            'gsu_survey_name');
        $this->addColumn(
            "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
            'gsu_has_pdf');

        $this->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN 0 ELSE 1 END',
            'is_completed');
        $this->addColumn(
            'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END',
            'can_be_taken');
        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END",
            'row_class');
        $this->addColumn(
            "CASE WHEN grc_success = 1 AND "
            . "((gr2o_email IS NOT NULL AND gr2o_email != '' AND (gto_id_relationfield IS NULL OR gto_id_relationfield < 1) AND gr2o_mailable >= gsu_mail_code) OR "
            . "(grr_email IS NOT NULL AND grr_email != '' AND gto_id_relationfield > 0 AND grr_mailable >= gsu_mail_code))"
            . " AND ggp_member_type = 'respondent' AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gr2t_mailable >= gsu_mail_code THEN 1 ELSE 0 END",
            'can_email');

        $this->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');
        $this->addColumn(
            "CASE WHEN gems__staff.gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    )
                END",
            'assigned_by');
        $this->addColumn("'token'", \Gems\Model::ID_TYPE);

        $this->addColumn(TokenRepository::getStatusExpression(), 'token_status');

        /*    TRIM(CONCAT(
                CASE WHEN gto_created = gto_changed OR DATEDIFF(CURRENT_TIMESTAMP, gto_changed) > 0 THEN '' ELSE 'changed' END,
                ' ',
                CASE WHEN DATEDIFF(CURRENT_TIMESTAMP, gto_created) > 0 THEN '' ELSE 'created' END
            ))"), 'row_class'); // */

        // $this->metaModel->set('gsu_id_primary_group', 'default', 800);

//        $this->setOnSave('gto_mail_sent_date', array($this, 'saveCheckedMailDate'));
//        $this->setOnSave('gto_mail_sent_num',  array($this, 'saveCheckedMailNum'));


        $this->applyFormatting();
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @return \Gems\Tracker\Model\StandardTokenModel
     */
    public function applyFormatting()
    {
        $this->metaModel->resetOrder();

        // Token id & respondent
        $this->metaModel->set('gto_id_token',           'label', $this->_('Token'),
            'elementClass', 'Exhibitor',
            'formatFunction', 'strtoupper'
        );
        $this->metaModel->set('gr2o_patient_nr',        'label', $this->_('Respondent nr'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('respondent_name',        'label', $this->_('Respondent name'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gto_id_organization',    'label', $this->_('Organization'),
            'elementClass', 'Exhibitor',
            'multiOptions', $this->organizationRepository->getOrganizationsWithRespondents()
        );

        // Track, round & survey
        $this->metaModel->set('gtr_track_name',         'label', $this->_('Track'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gr2t_track_info',        'label', $this->_('Description'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gto_round_description',  'label', $this->_('Round'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gsu_survey_name',        'label', $this->_('Survey'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('ggp_name',               'label', $this->_('Assigned to'),
            'elementClass', 'Exhibitor'
        );

        // Token, editable part
        $manual = $this->translatedUtil->getDateCalculationOptions();
        $this->metaModel->set('gto_valid_from_manual',  [
            'label' => $this->_('Set valid from'),
            'description' => $this->_('Manually set dates are fixed and will never be (re)calculated.'),
            'elementClass' => 'OnOffEdit',
            'multiOptions' => $manual,
            'separator' => ' ',
            ]);
        $this->metaModel->set('gto_valid_from', [
            'label' => $this->_('Valid from'),
            'elementClass' => 'Date',
            'tdClass' => 'date',
            AbstractDateType::$whenDateEmptyKey => $this->_('never'),
            MetaModelInterface::TYPE_ID => TokenValidFromType::class,
            ]);
        $this->metaModel->set('gto_valid_until_manual', [
            'label' => $this->_('Set valid until'),
            'description' => $this->_('Manually set dates are fixed and will never be (re)calculated.'),
            'elementClass' => 'OnOffEdit',
            'multiOptions' => $manual,
            'separator' => ' ',
            ]);
        $this->metaModel->set('gto_valid_until', [
            'label' => $this->_('Valid until'),
            'elementClass' => 'Date',
            'tdClass' => 'date',
            AbstractDateType::$whenDateEmptyKey => $this->_('forever'),
            MetaModelInterface::TYPE_ID => TokenValidUntilType::class,
            AfterDateModelValidator::$afterDateFieldKey => 'gto_valid_from',
            AfterDateModelValidator::$afterDateMessageKey => $this->_('The valid after date should be later than the valid for date!'),
            'validator[after]' => AfterDateModelValidator::class
            ]);
        $this->metaModel->set('gto_comment',            'label', $this->_('Comments'),
            'cols', 50,
            'elementClass', 'Textarea',
            'rows', 3,
            'tdClass', 'pre'
        );

        // Token, display part
        $this->metaModel->set('gto_mail_sent_date', [
            'label' => $this->_('Last contact'),
            'elementClass' => 'Exhibitor',
            AbstractDateType::$whenDateEmptyKey => $this->_('never'),
            AbstractDateType::$whenDateEmptyClassKey => 'disabled',
            'tdClass' => 'date',
            ]);
        $this->metaModel->set('gto_mail_sent_num',      'label', $this->_('Number of contact moments'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gto_completion_time', [
            'label' => $this->_('Completed'),
            'elementClass' => 'Exhibitor',
            AbstractDateType::$whenDateEmptyKey => $this->_('n/a'),
            AbstractDateType::$whenDateEmptyClassKey => 'disabled',
            'tdClass' => 'date',
            ]);
        $this->metaModel->set('gto_duration_in_sec',    'label', $this->_('Duration in seconds'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gto_result',             'label', $this->_('Score'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('grc_description',        'label', $this->_('Reception code'),
            'formatFunction', array($this->translate, '_'),
            'elementClass', 'Exhibitor'
        );
        $this->metaModel->set('gto_changed', [
            'label' => $this->_('Changed on'),
            'elementClass' => 'Exhibitor',
            AbstractDateType::$whenDateEmptyKey => $this->_('unknown'),
            AbstractDateType::$whenDateEmptyClassKey => 'disabled',
            ]);
        $this->metaModel->set('assigned_by',            'label', $this->_('Assigned by'),
            'elementClass', 'Exhibitor'
        );

        $this->applyMask();

        return $this;
    }
}