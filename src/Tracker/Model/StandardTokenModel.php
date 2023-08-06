<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use DateTimeImmutable;
use DateTimeInterface;

use Gems\Date\Period;
use Gems\Model\Type\TokenValidFromType;
use Gems\Model\Type\TokenValidUntilType;
use Gems\Tracker\Model\Dependency\TokenModelTimeDependency;
use Gems\User\User;
use Gems\Util\Translated;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\AbstractDateType;
use Zalt\Validator\Model\AfterDateModelValidator;

/**
 * The StandardTokenModel is the model used to display tokens
 * in e.g. browse tables. It can also be used to edit standard
 * tokens, though track engines may supply different models for
 * editing, as the SingleSurveyTokeModel does.
 *
 * The standard token model combines all possible information
 * about the token from the tables:
 * - gems__groups
 * - gems__organizations
 * - gems__reception_codes
 * - gems__respondent2org
 * - gems__respondent2track
 * - gems__respondents
 * - gems__staff (on created by)
 * - gems__surveys
 * - gems__tracks
 *
 * The \MUtil\Registry\TargetInterface is implemented so that
 * these models can take care of their own formatting.
 *
 * @see \Gems\Tracker\Engine\TrackEngineInterface
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class StandardTokenModel extends \Gems\Model\HiddenOrganizationModel
{
    /**
     * @var boolean When true the default settings are date only values
     */
    public static $dateOnlyDefault = true;
    
    /**
     * @var string Format to use for whole date tokens
     */
    public static $dateOnlyFormat = 'd-m-Y';

    /**
     * @var string \Gems\Util\Translated Format function to use for whole date tokens after date
     */
    public static $dateOnlyTranslatedFrom = 'formatDateNever';

    /**
     * @var string \Gems\Util\Translated Format function to use for whole date tokens for date
     */
    public static $dateOnlyTranslatedUntil = 'formatDateForever';
    
    /**
     * @var string Format to use for date/time tokens
     */
    public static $dateTimeFormat = 'd-m-Y H:i';

    /**
     * @var string \Gems\Util\Translated Format function to use for date/time tokens after date
     */
    public static $dateTimeTranslatedFrom = 'formatDateTimeNever';

    /**
     * @var string \Gems\Util\Translated Format function to use for date/time tokens for date
     */
    public static $dateTimeTranslatedUntil = 'formatDateTimeForever';
    
    /**
     *
     * @var boolean When true the labels of wholly masked items are removed
     */
    protected bool $hideWhollyMasked = true;

    /**
     *
     * @var boolean Set to true when data in the respondent2track table must be saved as well
     */
    protected $saveRespondentTracks = false;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Create the model with standard tables and calculated columns
     */
    public function __construct()
    {
        parent::__construct('token', 'gems__tokens', 'gto');

        if ($this->saveRespondentTracks) {
            // Set the correct prefix
            $this->saveRespondentTracks = 'gr2t';
        }

        $this->addTable(    'gems__tracks',               array('gto_id_track' => 'gtr_id_track'));
        $this->addTable(    'gems__surveys',              array('gto_id_survey' => 'gsu_id_survey'));
        $this->addTable(    'gems__groups',               array('gsu_id_primary_group' => 'ggp_id_group'));
        $this->addTable(    'gems__respondents',          array('gto_id_respondent' => 'grs_id_user'));
        $this->addTable(    'gems__respondent2org',       array('gto_id_organization' => 'gr2o_id_organization', 'gto_id_respondent' => 'gr2o_id_user'));
        $this->addTable(    'gems__respondent2track',     array('gto_id_respondent_track' => 'gr2t_id_respondent_track'), $this->saveRespondentTracks);
        $this->addTable(    'gems__organizations',        array('gto_id_organization' => 'gor_id_organization'));
        $this->addTable(    'gems__reception_codes',      array('gto_reception_code' => 'grc_id_reception_code'));
        $this->addTable(    'gems__rounds',               array('gto_id_round' => 'gro_id_round'));
        $this->addLeftTable('gems__staff',                array('gto_created_by' => 'gems__staff.gsf_id_user'));
        $this->addLeftTable('gems__track_fields',         array('gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"'));       // Add relation fields
        $this->addLeftTable('gems__respondent_relations', array('gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent')); // Add relation

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
        $this->addColumn(new \Zend_Db_Expr("'token'"), \Gems\Model::ID_TYPE);
        /*    TRIM(CONCAT(
                CASE WHEN gto_created = gto_changed OR DATEDIFF(CURRENT_TIMESTAMP, gto_changed) > 0 THEN '' ELSE 'changed' END,
                ' ',
                CASE WHEN DATEDIFF(CURRENT_TIMESTAMP, gto_created) > 0 THEN '' ELSE 'created' END
            ))"), 'row_class'); // */

        if ($this->saveRespondentTracks) {
            // The save order is reversed in this case.
            $this->_saveTables = array_reverse($this->_saveTables);
        }

        $this->set('gsu_id_primary_group', 'default', 800);

        $this->setOnSave('gto_mail_sent_date', array($this, 'saveCheckedMailDate'));
        $this->setOnSave('gto_mail_sent_num',  array($this, 'saveCheckedMailNum'));

        $this->useTokenAsKey();
    }

    /**
     * Function to check whether the mail_sent should be reset
     *
     * @param boolean $isNew True when a new item is being saved
     * @param array $context The values being saved
     * @return boolean True when the change should be triggered
     */
    private function _checkForMailSent($isNew, array $context)
    {
        // Never change on new tokens
        if ($isNew) {
            return false;
        }

        // Only act on existing valid from date
        if (! (isset($context['gto_valid_from']) && $context['gto_valid_from'])) {
            return false;
        }

        // There must be data to reset
        $hasSentDate = isset($context['gto_mail_sent_date']) && $context['gto_mail_sent_date'];
        if (! ($hasSentDate || (isset($context['gto_mail_sent_num']) && $context['gto_mail_sent_num']))) {
            return false;
        }

        // When only the sent_num is set, then clear the existing data
        if (! $hasSentDate) {
            return true;
        }

        if ($context['gto_valid_from'] instanceof DateTimeInterface) {
            $start = $context['gto_valid_from'];
        } else {
            $start = DateTimeImmutable::createFromFormat($this->get('gto_valid_from', 'dateFormat'), $context['gto_valid_from']);
        }

        if ($context['gto_mail_sent_date'] instanceof DateTimeInterface) {
            $sent = $context['gto_mail_sent_date'];
        } else {
            $sent = DateTimeImmutable::createFromFormat($this->get('gto_valid_from', 'gto_mail_sent_date'), $context['gto_mail_sent_date']);
        }

        return $start->isLater($sent);
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

        $currentUser = $this->currentUserRepository->getCurrentUser();

        if ($currentUser instanceof User) {
            $this->addColumn(
                $this->util->getTokenData()->getShowAnswersExpression($currentUser->getGroupId(true)),
                'show_answers'
            );
        }
        
        //If we are allowed to see who filled out a survey, modify the model accordingly
        if ($currentUser instanceof User && $currentUser->hasPrivilege('pr.respondent.who')) {
            $this->addLeftTable('gems__staff', array('gto_by' => 'gems__staff_2.gsf_id_user'));
            $this->addColumn(new \Zend_Db_Expr('CASE
                WHEN gems__staff_2.gsf_id_user IS NULL THEN COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)
                ELSE CONCAT_WS(
                    " ",
                    CONCAT(COALESCE(gems__staff_2.gsf_last_name, "-"), ","),
                    gems__staff_2.gsf_first_name,
                    gems__staff_2.gsf_surname_prefix
                    )
                END'), 'ggp_name');
        } else {
            $this->set('ggp_name', 'column_expression', new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)'));
        }
        if ($currentUser instanceof User && $currentUser->hasPrivilege('pr.respondent.result')) {
            $this->addColumn('gto_result', 'calc_result', 'gto_result');
        } else {
            $this->addColumn(new \Zend_Db_Expr('NULL'), 'calc_result', 'gto_result');
        }
        $this->addColumn($this->util->getTokenData()->getStatusExpression(), 'token_status');
        $this->set('forgroup', 'column_expression', new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, ggp_name)'));
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @return \Gems\Tracker\Model\StandardTokenModel
     */
    public function applyFormatting()
    {
        $this->resetOrder();

        $dbLookup   = $this->util->getDbLookup();

        // Token id & respondent
        $this->set('gto_id_token',           'label', $this->_('Token'),
                'elementClass', 'Exhibitor',
                'formatFunction', 'strtoupper'
                );
        $this->set('gr2o_patient_nr',        'label', $this->_('Respondent nr'),
                'elementClass', 'Exhibitor'
                );
        $this->set('respondent_name',        'label', $this->_('Respondent name'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_id_organization',    'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $dbLookup->getOrganizationsWithRespondents()
                );

        // Track, round & survey
        $this->set('gtr_track_name',         'label', $this->_('Track'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gr2t_track_info',        'label', $this->_('Description'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_round_description',  'label', $this->_('Round'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_survey_name',        'label', $this->_('Survey'),
                'elementClass', 'Exhibitor'
                );
        $this->set('ggp_name',               'label', $this->_('Assigned to'),
                'elementClass', 'Exhibitor'
                );

        // Token, editable part
        $manual = $this->translatedUtil->getDateCalculationOptions();
        $this->set('gto_valid_from_manual',  [
            'label' => $this->_('Set valid from'),
            'description' => $this->_('Manually set dates are fixed and will never be (re)calculated.'),
            'elementClass' => 'OnOffEdit',
            'multiOptions' => $manual,
            'separator' => ' ',
        ]);
        $this->set('gto_valid_from', [
            'label' => $this->_('Valid from'),
            'elementClass' => 'Date',
            'tdClass' => 'date',
            MetaModelInterface::TYPE_ID => TokenValidFromType::class,
            ]);
        $this->set('gto_valid_from', $this->getTokenDateSettings(true));
        $this->setOnLoad('gto_valid_from', [$this, 'formatValidFromDate']);

        $this->set('gto_valid_until_manual',  [
            'label' => $this->_('Set valid from'),
            'description' => $this->_('Manually set dates are fixed and will never be (re)calculated.'),
            'elementClass' => 'OnOffEdit',
            'multiOptions' => $manual,
            'separator' => ' ',
        ]);
        $this->set('gto_valid_until', [
            'label' => $this->_('Valid until'),
            'tdClass' => 'date',
            AbstractDateType::$whenDateEmptyKey => $this->_('forever'),
            MetaModelInterface::TYPE_ID => TokenValidUntilType::class,
            AfterDateModelValidator::$afterDateFieldKey => 'gto_valid_from',
            AfterDateModelValidator::$afterDateMessageKey => $this->_('The valid after date should be later than the valid for date!'),
            'validator[after]' => AfterDateModelValidator::class,
        ]);
        $this->set('gto_valid_until', $this->getTokenDateSettings(false));
        $this->setOnLoad('gto_valid_until', [$this, 'formatValidUntilDate']);
        $this->set('gto_comment',            'label', $this->_('Comments'),
                'cols', 50,
                'elementClass', 'Textarea',
                'rows', 3,
                'tdClass', 'pre'
                );

        // Token, display part
        $this->set('gto_mail_sent_date',     'label', $this->_('Last contact'),
                'elementClass', 'Exhibitor',
                'formatFunction', $this->translatedUtil->formatDateTimeNever,
                'tdClass', 'date');
        $this->set('gto_mail_sent_num',      'label', $this->_('Number of contact moments'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_completion_time',    'label', $this->_('Completed'),
                'elementClass', 'Exhibitor',
                'formatFunction', $this->translatedUtil->formatDateTimeNa,
                'tdClass', 'date');
        $this->set('gto_duration_in_sec',    'label', $this->_('Duration in seconds'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_result',             'label', $this->_('Score'),
                'elementClass', 'Exhibitor'
                );
        $this->set('grc_description',        'label', $this->_('Reception code'),
                'formatFunction', array($this->translate, '_'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_changed',            'label', $this->_('Changed on'),
                'elementClass', 'Exhibitor',
                'formatFunction', $this->translatedUtil->formatDateUnknown
                );
        $this->set('assigned_by',            'label', $this->_('Assigned by'),
                'elementClass', 'Exhibitor'
                );

        $this->applyMask();

        return $this;
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @return \Gems\Tracker\Model\StandardTokenModel
     */
    public function applyInsertionFormatting()
    {
        $this->set('gto_id_token', 'elementClass', 'None');

        return $this;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->translate && $this->util;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \DateTimeInterface format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return DateTimeInterface|\Zend_Db_Expr|null
     */
    public function formatValidFromDate($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        // We set these values here instead of in a dependency, because the parent onLoad function is called
        // before the dependencies are.
        if (isset($context['gro_valid_after_unit'])) {
            $this->set($name, $this->getTokenDateSettings(true, $context['gro_valid_after_unit']));
        }

        return parent::formatLoadDate($value, $isNew, $name, $context, $isPost);
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \DateTimeInterface format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return DateTimeInterface|\Zend_Db_Expr|null
     */
    public function formatValidUntilDate($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        // We set these values here instead of in a dependency, because the parent onLoad function is called
        // before the dependencies are.
        if (isset($context['gro_valid_for_unit'])) {
            $this->set($name, $this->getTokenDateSettings(false, $context['gro_valid_for_unit']));
        }

        return parent::formatLoadDate($value, $isNew, $name, $context, $isPost);
    }

    /**
     * @param bool $validFrom valid from or valid intil?
     * @param string $periodUnit Single char
     * @return array modelsettings
     */
    public function getTokenDateSettings($validFrom, $periodUnit = null)
    {
        if ($periodUnit) {
            $useFullDate = Period::isDateType($periodUnit);
        } else {
            $useFullDate = self::$dateOnlyDefault;
        }
        if ($useFullDate) {
            $output['dateFormat'] = self::$dateOnlyFormat;
            if ($validFrom) {
                $output['formatFunction'] = [$this->translatedUtil, self::$dateOnlyTranslatedFrom];
            } else {
                $output['formatFunction'] = [$this->translatedUtil, self::$dateOnlyTranslatedUntil];
            }
        } else {
            $output['dateFormat'] = self::$dateTimeFormat;
            if ($validFrom) {
                $output['formatFunction'] = [$this->translatedUtil, self::$dateTimeTranslatedFrom];
            } else {
                $output['formatFunction'] = [$this->translatedUtil, self::$dateTimeTranslatedUntil];
            }
        }
        
        return $output;
    }
    
    /**
     * A ModelAbstract->setOnSave() function that can transform the saved item.
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function saveCheckedMailDate($value, $isNew = false, $name = null, array $context = array())
    {
        if ($this->_checkForMailSent($isNew, $context)) {
            return null;
        }

        return $this->formatSaveDate($value, $isNew, $name, $context);
    }

    /**
     * A ModelAbstract->setOnSave() function that can transform the saved item.
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function saveCheckedMailNum($value, $isNew = false, $name = null, array $context = array())
    {
        if ($this->_checkForMailSent($isNew, $context)) {
            return 0;
        }

        return $value;
    }

    public function useRespondentTrackAsKey()
    {
        $this->setKeys($this->_getKeysFor('gems__respondent2org') + $this->_getKeysFor('gems__tracks'));

        return $this;
    }

    public function useTokenAsKey()
    {
        $this->setKeys($this->_getKeysFor('gems__tokens'));

        return $this;
    }
}