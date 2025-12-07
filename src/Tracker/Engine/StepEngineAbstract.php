<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Engine;

use DateTimeImmutable;
use DateTimeInterface;

use Gems\Condition\ConditionLoader;
use Gems\Condition\RoundConditionInterface;
use Gems\Date\Period;
use Gems\Db\ResultFetcher;
use Gems\Exception\Coding;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Project\ProjectSettings;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Tracker;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Tracker\Model\RoundModel;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\Token;
use Gems\Tracker\TrackEvents;
use Gems\Translate\DbTranslationRepository;
use Gems\User\User;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Laminas\Filter\Digits;
use Laminas\I18n\Filter\NumberFormat;
use Laminas\I18n\Validator\IsInt;
use MUtil\Model\TableModel;
use MUtil\Ra;
use MUtil\Translate\Translator;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;

/**
 * Parent class for all engines that calculate dates using information
 * from other rounds.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class StepEngineAbstract extends TrackEngineAbstract
{
    /**
     * Database stored constant value for using an answer in a survey as date source
     */
    const ANSWER_TABLE = 'ans';

    /**
     * Database stored constant value for using an appointment as date source
     */
    const APPOINTMENT_TABLE = 'app';

    /**
     * Database stored constant value for using nothing as a date source
     */
    const NO_TABLE = 'nul';

    /**
     * Database stored constant value for using a track field as date source
     */
    const RESPONDENT_TRACK_TABLE = 'rtr';

    /**
     * Database stored constant value for using a respondent as date source
     */
    const RESPONDENT_TABLE = 'res';

    /**
     * Database stored constant value for using a token as date source
     */
    const TOKEN_TABLE = 'tok';

    protected ?User $currentUser;

    /**
     *
     * @var string Class name for creating the round model.
     */
    protected string $_roundModelClass = TableModel::class;

    public function __construct(
        array $trackData,
        ResultFetcher $resultFetcher,
        Tracker $tracker,
        DbTranslationRepository $dbTranslationRepository,
        ProjectOverloader $overloader,
        Translator $translator,
        TrackEvents $trackEvents,
        ConditionLoader $conditionLoader,
        TrackDataRepository $trackDataRepository,
        OrganizationRepository $organizationRepository,
        Translated $translatedUtil,
        protected readonly Locale $locale,
        protected readonly ProjectSettings $projectSettings,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct(
            $trackData,
            $resultFetcher,
            $tracker,
            $dbTranslationRepository,
            $overloader,
            $translator,
            $trackEvents,
            $conditionLoader,
            $trackDataRepository,
            $organizationRepository,
            $translatedUtil,
        );
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Helper function for default handling of multi options value sets
     *
     * @param MetaModelInterface $model
     * @param string $fieldName
     * @param array $options
     * @param array $itemData    The current items data
     * @return bool True if the update changed values (usually by changed selection lists).
     */
    protected function _applyOptions(MetaModelInterface $model, string $fieldName, array $options, array &$itemData): bool
    {
        if ($options) {
            $model->set($fieldName, 'multiOptions', $options);

            if (! array_key_exists($itemData[$fieldName], $options)) {
                // Set the value to the first possible value
                reset($options);
                $itemData[$fieldName] = key($options);

                return true;
            }
        } else {
            $model->del($fieldName, 'label');
        }
        return false;
    }

    /**
     * Returns true if the source uses values from another token.
     *
     * @param string $source The current source
     * @return boolean
     */
    protected function _sourceUsesSurvey(string $source): bool
    {
        switch ($source) {
            case self::APPOINTMENT_TABLE:
            case self::NO_TABLE:
            case self::RESPONDENT_TRACK_TABLE:
            case self::RESPONDENT_TABLE:
                return false;

            default:
                return true;
        }
    }

    /**
     * Set the after dates to be listed for this item and the way they are displayed (if at all)
     *
     * @param MetaModelInterface $model The round model
     * @param array $itemData    The current items data
     * @param string $language   (ISO) language string
     * @return bool True if the update changed values (usually by changed selection lists).
     */
    protected function applyDatesValidAfter(MetaModelInterface $model, array &$itemData, string $language): bool
    {
        // Set the after date fields that can be chosen for the current values
        $dateOptions = $this->getDateOptionsFor($itemData['gro_valid_after_source'], $itemData['gro_valid_after_id'], $language);

        return $this->_applyOptions($model, 'gro_valid_after_field', $dateOptions, $itemData);
    }

    /**
     * Set the for dates to be listed for this item and the way they are displayed (if at all)
     *
     * @param MetaModelInterface $model The round model
     * @param array $itemData    The current items data
     * @param string $language   (ISO) language string
     * @return bool True if the update changed values (usually by changed selection lists).
     */
    protected function applyDatesValidFor(MetaModelInterface $model, array &$itemData, string $language): bool
    {
        $dateOptions = $this->getDateOptionsFor($itemData['gro_valid_for_source'], $itemData['gro_valid_for_id'] ? $itemData['gro_valid_for_id'] : null, $language);

        if ($itemData['gro_id_round'] == $itemData['gro_valid_for_id']) {
            // Cannot use the valid until of the same round to calculate that valid until
            unset($dateOptions['gto_valid_until']);
        }

        if ($itemData['gro_valid_for_source'] == self::NO_TABLE) {
            $model->del('gro_valid_for_unit', 'label');
            $model->del('gro_valid_for_length', 'label');
        }
        return $this->_applyOptions($model, 'gro_valid_for_field', $dateOptions, $itemData);
    }

    protected function applyOrganizationRounds(MetaModelInterface $model, array $itemData): void
    {
        if ($itemData['org_specific_round'] == 0) {
            $model->set('organizations', [
               'elementClass' => 'None',
            ]);
        }
    }

    /**
     * Apply respondent relation settings to the round model
     *
     * For respondent surveys, we allow to set a relation, with possible choices:
     *
     *  null => the respondent
     *  0    => undefined (specifiy when assigning)
     *  >0   => the id of the track field of type relation to use
     *
     * @param MetaModelInterface $model The round model
     * @param array $itemData    The current items data
     *
     * @return void
     */
    protected function applyRespondentRelation(MetaModelInterface $model, array &$itemData): void
    {
        $model->set('gro_id_survey', [
            'autoSubmit' => true,
        ]);
        if (!empty($itemData['gro_id_survey']) && $model->has('gro_id_relationfield')) {
            $forStaff = $this->tracker->getSurvey($itemData['gro_id_survey'])->isTakenByStaff();
            if (!$forStaff) {
                $empty = array('-1' => $this->translator->_('Patient'));

                $relations = $this->getRespondentRelationFields();
                if (!empty($relations)) {
                    $relations = $empty + $relations;
                    $model->set('gro_id_relationfield', 'label', $this->translator->_('Assigned to'), 'multiOptions', $relations, 'order', 25);
                }
                $model->del('ggp_name');
            } else {
                $model->set('ggp_name', 'label', $this->translator->_('Assigned to'), 'elementClass', 'Exhibitor', 'order', 25);
                $model->set('gro_id_relationfield', 'elementClass', 'hidden');

                $itemData['ggp_name'] = $this->resultFetcher->fetchOne('select ggp_name from gems__groups join gems__surveys on ggp_id_group = gsu_id_primary_group and gsu_id_survey = ?', [$itemData['gro_id_survey']]);
                if (!is_null($itemData['gro_id_relationfield'])) {
                    $itemData['gro_id_relationfield'] = null;
                }
            }
        } else {
            $model->del('gro_id_relationfield', 'label');
            $model->del('ggp_name');
            if ($model->has('gro_id_relationfield')) {
                $itemData['gro_id_relationfield'] = null;
            }
        }
    }

    /**
     * Set the surveys to be listed as valid after choices for this item and the way they are displayed (if at all)
     *
     * @param MetaModelInterface $model The round model
     * @param array $itemData    The current items data
     * @return bool True if the update changed values (usually by changed selection lists).
     */
    abstract protected function applySurveyListValidAfter(MetaModelInterface $model, array &$itemData): bool;

    /**
     * Set the surveys to be listed as valid for choices for this item and the way they are displayed (if at all)
     *
     * @param MetaModelInterface $model The round model
     * @param array $itemData    The current items data
     * @return bool True if the update changed values (usually by changed selection lists).
     */
    abstract protected function applySurveyListValidFor(MetaModelInterface $model, array &$itemData): bool;

    /**
     *
     * @param ?DateTimeInterface $startDate
     * @param string $type
     * @param int $period
     * @return ?DateTimeInterface
     */
    protected function calculateFromDate(DateTimeInterface|null $startDate, string $type, int $period): ?DateTimeInterface
    {
        return Period::applyPeriod($startDate, $type, $period);
    }

    /**
     *
     * @param ?DateTimeInterface $startDate
     * @param string $type
     * @param int $period
     * @return ?DateTimeInterface
     */
    protected function calculateUntilDate(DateTimeInterface|null $startDate, string $type, int $period): ?DateTimeInterface
    {
        $date = $this->calculateFromDate($startDate, $type, $period);

        if ($date instanceof DateTimeImmutable) {
            if (Period::isDateType($type)) {
                // Make sure day based units are valid until the end of the day.
                return $date->setTime(23,59,59);
            }
            return $date;
        }
        return null;
    }

    /**
     * Check if the token should be enabled / disabled due to conditions
     *
     * @param \Gems\Tracker\Token $token
     * @param array $round
     * @param int   $userId Id of the user who takes the action (for logging)
     * @param RespondentTrack $respTrack Current respondent track
     * @return int The number of tokens changed by this code
     */
    protected function checkTokenCondition(Token $token, array $round, int $userId, RespondentTrack $respTrack): int
    {
        $skipCode = ReceptionCodeRepository::RECEPTION_SKIP;

        // Only if we have a condition, the token is not yet completed and
        // receptioncode is ok or skip we evaluate the condition
        if (empty($round['gro_condition']) ||
            $token->isCompleted() ||
            !($token->getReceptionCode()->isSuccess() || $token->getReceptionCode()->getCode() == $skipCode)) {

            return 0;
        }

        $changed   = 0;
        /** @var RoundConditionInterface $condition */
        $condition = $this->conditionLoader->loadCondition($round['gro_condition']);
        $newStatus = $condition->isRoundValid($token);
        $oldStatus = $token->getReceptionCode()->isSuccess();

        if ($newStatus !== $oldStatus) {
            $changed = 1;
            if ($newStatus == false) {
                $message = $this->translator->_('Skipped by condition %s: %s');
                $newCode = $skipCode;
            } else {
                $message = $this->translator->_('Activated by condition %s: %s');
                $newCode = ReceptionCodeRepository::RECEPTION_OK;
            }

            $token->setReceptionCode($newCode,
                    sprintf($message, $condition->getName(), $condition->getRoundDisplay($token->getTrackId(), $token->getRoundId())),
                    $userId);
        }

        return $changed;
    }

    /**
     * Check the valid from and until dates for this token
     *
     * @param \Gems\Tracker\Token $token
     * @param array $round
     * @param int   $userId Id of the user who takes the action (for logging)
     * @param RespondentTrack $respTrack Current respondent track
     * @return int 1 if the token has changed
     */
    protected function checkTokenDates(Token $token, array $round, int $userId, RespondentTrack $respTrack): int
    {
        $skipCode = ReceptionCodeRepository::RECEPTION_SKIP;

        // Change only not-completed tokens with a positive successcode where at least one date
        // is not set by user input
        if ($token->isCompleted() || !$token->getReceptionCode()->isSuccess() || ($token->isValidFromManual() && $token->isValidUntilManual())) {
            // When a token has a skipcode, due to age being out of limits, changing the date might change the condition
            // For this reason we recalculate the date when it was skipped due to a condition
            if ($token->getReceptionCode()->getCode() == $skipCode && !empty($round['gro_condition'])) {
                // Just continue, code was split for readabitily
            } else {
                return 0;
            }
        }

        if ($token->isValidFromManual()) {
            $validFrom = $token->getValidFrom();
        } else {
            $fromDate  = $this->getValidFromDate(
                    $round['gro_valid_after_source'], $round['gro_valid_after_field'], $round['gro_valid_after_id'], $token, $respTrack
            );
            $validFrom = $this->calculateFromDate(
                    $fromDate, $round['gro_valid_after_unit'], $round['gro_valid_after_length']
            );
        }

        if ($token->isValidUntilManual()) {
            $validUntil = $token->getValidUntil();
        } else {
            $untilDate  = $this->getValidUntilDate(
                    $round['gro_valid_for_source'], $round['gro_valid_for_field'], $round['gro_valid_for_id'], $token, $respTrack, $validFrom
            );
            $validUntil = $this->calculateUntilDate(
                    $untilDate, $round['gro_valid_for_unit'], $round['gro_valid_for_length']
            );
        }

        return $token->setValidFrom($validFrom, $validUntil, $userId);
    }

    /**
     * Check the valid from and until dates in the track starting at a specified token
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param \Gems\Tracker\Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Tracker\Token $skipToken Optional token to skip in the recalculation
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(RespondentTrack $respTrack, Token $startToken, $userId, ?Token $skipToken = null): int
    {
        // Make sure the rounds are loaded
        $this->_ensureRounds();

        // Make sure the tokens are loaded and linked.
        $respTrack->getTokens();

        // Go
        $changed = 0;
        $token = $startToken;
        while ($token) {
            //Only process the token when linked to a round
            $round   = false;
            $changes = 0;
            if (array_key_exists($token->getRoundId(), $this->_rounds)) {
                $round = $this->_rounds[$token->getRoundId()];
            }

            if ($round && $token !== $skipToken) {
                $changes = $this->checkTokenCondition($token, $round, $userId, $respTrack);
                $changes += $this->checkTokenDates($token, $round, $userId, $respTrack);
            }

            // If condition changed and dates changed, we only signal one change
            $changed += min($changes, 1);
            $token = $token->getNextToken();
        }

        return $changed;
    }

    /**
     * Check the valid from and until dates in the track
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFromStart(RespondentTrack $respTrack, int $userId): int
    {
        $token = $respTrack->getFirstToken();
        if ($token instanceof Token) {
            return $this->checkTokensFrom($respTrack, $token, $userId);
        } else {
            return 0;
        }
    }

    /**
     * Changes the display of gro_valid_[after|for]_field into something readable
     *
     * @param mixed $value The value being saved
     * @param boolean $new True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The value to use
     */
    public function displayDateCalculation(mixed $value, bool $new, string $name, array $context = []): string
    {
        $fieldBase = substr($name, 0, -5);  // Strip field

        // When always valid, just return nothing
        if ($context[$fieldBase . 'source'] == self::NO_TABLE) return '';

        $fields = $this->getDateOptionsFor(
                $context[$fieldBase . 'source'],
                $context[$fieldBase . 'id'],
                $this->locale->getLanguage()
                );

        if (isset($fields[$context[$fieldBase . 'field']])) {
            $field = $fields[$context[$fieldBase . 'field']];
        } else {
            $field = $context[$fieldBase . 'field'];
        }

        if ($context[$fieldBase . 'length'] > 0) {
            $format = $this->translator->_('%s plus %s %s');
        } elseif($context[$fieldBase . 'length'] < 0) {
            $format = $this->translator->_('%s minus %s %s');
        } else {
            $format = $this->translator->_('%s');
        }

        $units = $this->translatedUtil->getPeriodUnits();
        if (isset($units[$context[$fieldBase . 'unit']])) {
            $unit = $units[$context[$fieldBase . 'unit']];
        } else {
            $unit = $context[$fieldBase . 'unit'];
        }

        // \MUtil\EchoOut\EchoOut::track(func_get_args());
        return sprintf($format, $field, abs($context[$fieldBase . 'length']), $unit);
    }

    /**
     * Changes the display of gro_valid_[for|after]_id into something readable
     *
     * Makes it empty when not applicable
     *
     * @param mixed $value The value being saved
     * @param bool $new True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The value to use
     */
    public function displayRoundId(mixed $value, bool $new, string $name, array $context = [])
    {
        $fieldSource = substr($name, 0, -2) . 'source';

        if ($this->_sourceUsesSurvey($context[$fieldSource])) {
            return $value;
        }

        return '';
    }

    /**
     * An array of snippet names for displaying the answers to a survey.
     *
     * @return array if string snippet names
     */
    public function getAnswerSnippetNames(): array
    {
        return ['Tracker\\Answers\\TrackAnswersModelSnippet'];
    }

    /**
     * Returns the date fields selectable using the current source
     *
     * @param string $sourceType The date list source as defined by this object
     * @param int $roundId  \Gems round id
     * @param string $language   (ISO) language string
     * @return array
     */
    protected function getDateOptionsFor(string $sourceType, int|null $roundId, string $language): array
    {
        switch ($sourceType) {
            case self::NO_TABLE:
                return array();

            case self::ANSWER_TABLE:
                if (! isset($this->_rounds[$roundId], $this->_rounds[$roundId]['gro_id_survey'])) {
                    return [];
                }
                $surveyId = $this->_rounds[$roundId]['gro_id_survey'];
                $survey = $this->tracker->getSurvey($surveyId);
                return $survey->getDatesList($language);

            case self::APPOINTMENT_TABLE:
                return $this->_fieldsDefinition->getFieldLabelsOfType(FieldsDefinition::TYPE_APPOINTMENT);

            case self::RESPONDENT_TRACK_TABLE:
                $results = [
                    'gr2t_start_date' => $this->translator->_('Track start'),
                    'gr2t_end_date'   => $this->translator->_('Track end'),
                    // 'gr2t_created'    => $this->translator->_('Track created'),
                ];

                return $results + $this->_fieldsDefinition->getFieldLabelsOfType([
                    FieldsDefinition::TYPE_DATE,
                    FieldsDefinition::TYPE_DATETIME,
                    ]);

            case self::RESPONDENT_TABLE:
                return [
                    'grs_birthday' => $this->translator->_('Birthday'),
                    'gr2o_created' => $this->translator->_('Respondent created'),
                    /*'gr2o_changed' => $this->translator->_('Respondent changed'),*/
                    ];

            case self::TOKEN_TABLE:
                return [
                    'gto_valid_from'      => $this->translator->_('Valid from'),
                    'gto_valid_until'     => $this->translator->_('Valid until'),
                    'gto_start_time'      => $this->translator->_('Start time'),
                    'gto_completion_time' => $this->translator->_('Completion date'),
                ];

        }

        return [];
    }

    /**
     * Get all respondent relation fields
     *
     * Returns an array of field id => field name
     *
     * @return array
     */
    public function getRespondentRelationFields(): array {
        $fields = [];
        $relationFields = $this->getFieldsOfType('relation');

        if (!empty($relationFields)) {
            $fieldNames = $this->getFieldNames();
            $fieldPrefix = FieldMaintenanceModel::FIELDS_NAME . FieldsDefinition::FIELD_KEY_SEPARATOR;
            foreach ($this->getFieldsOfType('relation') as $key => $field)
            {
                $id = str_replace($fieldPrefix, '', $key);
                $fields[$id] = $fieldNames[$key];
            }
        }

        return $fields;
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param bool $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return RoundModel
     */
    public function getRoundModel(bool $detailed, string $action): RoundModel
    {
        $model = parent::getRoundModel($detailed, $action);
        $metaModel = $model->getMetaModel();

        // Add information about surveys and groups
        $model->addLeftTable('gems__surveys', ['gro_id_survey' => 'gsu_id_survey']);
        $model->addLeftTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);
        $model->addLeftTable('gems__track_fields', ['gro_id_relationfield = gtf_id_field'], false, 'gtf');

        $model->addColumn(new Expression('COALESCE(gtf_field_name, ggp_name)'), 'ggp_name');
        $metaModel->set('ggp_name', ['label' => $this->translator->_('Assigned to')]);

        // Reset display order to class specific order
        $metaModel->resetOrder();
        if (! $detailed) {
            $metaModel->set('gro_id_order');
        }
        $metaModel->set('gro_id_track');
        $metaModel->set('gro_id_survey');
        $metaModel->set('gro_round_description');
        if ($detailed) {
            $metaModel->set('gro_id_order');
        }
        $metaModel->set('gro_icon_file');

        // Calculate valid from
        if ($detailed) {
            $html = \Zalt\Html\Html::create()->h4($this->translator->_('Valid from calculation'));
            $metaModel->set('valid_after', [
                'default' => $html,
                'label' => ' ',
                'elementClass' => 'html',
                'value' => $html
            ]);
        }
        $metaModel->set('gro_valid_after_source', [
            'label' => $this->translator->_('Date source'),
            'default' => self::TOKEN_TABLE,
            'elementClass' => 'Radio',
            'escape' => false,
            'required' => true,
            'autoSubmit' => true,
            'multiOptions' => $this->getSourceList(true, false, false)
        ]);
        $metaModel->set('gro_valid_after_id', [
            'label' => $this->translator->_('Round used'),
            'autoSubmit' => true
        ]);

        if ($detailed) {
            $periodUnits = $this->translatedUtil->getPeriodUnits();

            $metaModel->set('gro_valid_after_field', [
                'label' => $this->translator->_('Date used'),
                'default' => 'gto_valid_from',
                'autoSubmit' => true
            ]);
            $metaModel->set('gro_valid_after_length', [
                'label' => $this->translator->_('Add to date'),
                'description' => $this->translator->_('Can be negative'),
                'required' => true,
                'filter' => new NumberFormat(),
                'validator' => new IsInt()
            ]);
            $metaModel->set('gro_valid_after_unit', [
                'label' => $this->translator->_('Add to date unit'),
                'multiOptions' => $periodUnits
            ]);
        } else {
            $metaModel->set('gro_valid_after_source', [
                'label' => $this->translator->_('Source'),
                'tableDisplay' => 'small'
            ]);
            $metaModel->set('gro_valid_after_id', [
                'label' => $this->translator->_('Round'),
                'multiOptions' => $this->getRoundTranslations(),
                'tableDisplay' => 'small'
            ]);
            $metaModel->setOnLoad('gro_valid_after_id', [$this, 'displayRoundId']);
            $metaModel->set('gro_valid_after_field', [
                'label' => $this->translator->_('Date calculation'),
                'tableHeaderDisplay' => 'small',
            ]);
            $metaModel->setOnLoad('gro_valid_after_field', [$this, 'displayDateCalculation']);
            $metaModel->set('gro_valid_after_length');
            $metaModel->set('gro_valid_after_unit');
        }

        if ($detailed) {
            // Calculate valid until
            $html = \Zalt\Html\Html::create()->h4($this->translator->_('Valid for calculation'));
            $metaModel->set('valid_for', [
                'label' => ' ',
                'default' => $html,
                'elementClass' => 'html',
                'value' => $html
            ]);
        }

        $metaModel->set('gro_valid_for_source', [
            'label' => $this->translator->_('Date source'),
            'default' => self::TOKEN_TABLE,
            'elementClass' => 'Radio',
            'escape' => false,
            'required' => true,
            'autoSubmit' => true,
            'multiOptions' => $this->getSourceList(false, false, false)
        ]);
        $metaModel->set('gro_valid_for_id', [
            'label' => $this->translator->_('Round used'),
            'default' => '',
            'autoSubmit' => true,
        ]);

        if ($detailed) {
            $metaModel->set('gro_valid_for_field', [
                'label' => $this->translator->_('Date used'),
                'default' => 'gto_valid_from',
                'autoSubmit' => true,
            ]);
            $metaModel->set('gro_valid_for_length', [
                'label' => $this->translator->_('Add to date'),
                'description' => $this->translator->_('Can be negative'),
                'required' => true,
                'default' => 2,
                'filter' => new NumberFormat(),
                'validator' => new IsInt()
            ]);
            $metaModel->set('gro_valid_for_unit', [
                'label' => $this->translator->_('Add to date unit'),
                'multiOptions' => $periodUnits
            ]);

            // Calculate valid until
            $html = \Zalt\Html\Html::create()->h4($this->translator->_('Validity calculation'));
            $metaModel->set('valid_cond', [
                'label' => ' ',
                'default' => $html,
                'elementClass' => 'html',
                'value' => $html
            ]);

            // Continue with last round level items
            $metaModel->set('gro_condition');
            $metaModel->set('condition_display');
            $metaModel->set('gro_active');
            $metaModel->set('gro_changed_event');
        } else {
            $metaModel->set('gro_valid_for_source', [
                'label' => $this->translator->_('Source'),
                'tableDisplay' => 'small'
            ]);
            $metaModel->set('gro_valid_for_id', [
                'label' => $this->translator->_('Round'),
                'multiOptions' => $this->getRoundTranslations(),
                'tableDisplay' => 'small'
            ]);
            $metaModel->setOnLoad('gro_valid_for_id', [$this, 'displayRoundId']);
            $metaModel->set('gro_valid_for_field', [
                'label' => $this->translator->_('Date calculation'),
                'tableHeaderDisplay' => 'small'
            ]);
            $metaModel->setOnLoad('gro_valid_for_field', [$this, 'displayDateCalculation']);
            $metaModel->set('gro_valid_for_length');
            $metaModel->set('gro_valid_for_unit');
        }

        return $model;
    }

    /**
     * Get the display values for rounds
     *
     * @return array roundId => display string
     */
    protected function getRoundTranslations()
    {
        $this->_ensureRounds();

        return Ra::column('gro_id_order', $this->_rounds);
    }

    /**
     * Returns the source choices in an array.
     *
     * @param boolean $validAfter True if it concerns _valid_after_ dates
     * @param boolean $firstRound List for first round
     * @param boolean $detailed   Return extended info
     * @return array source_name => label
     */
    protected function getSourceList(bool $validAfter, bool $firstRound, bool $detailed = true): array
    {
        if (! ($validAfter || $this->projectSettings->isValidUntilRequired())) {
            $results[self::NO_TABLE] = [$this->translator->_('Does not expire')];
        }
        if (! ($validAfter && $firstRound)) {
            $results[self::ANSWER_TABLE] = [$this->translator->_('Answers'), $this->translator->_('Use an answer from a survey.')];
        }
        if ($this->_fieldsDefinition->hasAppointmentFields()) {
            $results[self::APPOINTMENT_TABLE] = [
                $this->translator->_('Appointment'),
                $this->translator->_('Use an appointment linked to this track.'),
            ];
        }
        if (! ($validAfter && $firstRound)) {
            $results[self::TOKEN_TABLE]  = [$this->translator->_('Token'), $this->translator->_('Use a standard token date.')];
        }
        $results[self::RESPONDENT_TRACK_TABLE] = [$this->translator->_('Track'), $this->translator->_('Use a track level date.')];
        $results[self::RESPONDENT_TABLE] = [$this->translator->_('Respondent'), $this->translator->_('Use a respondent level date.')];

        if ($detailed) {
            foreach ($results as $key => $value) {
                if (is_array($value)) {
                    $results[$key] = Html::raw(sprintf('<strong>%s</strong> %s', reset($value), next($value)));
                }
            }
        } else {
            foreach ($results as $key => $value) {
                if (is_array($value)) {
                    $results[$key] = reset($value);
                }
            }
        }

        return $results;
    }

    /**
     * Look up the survey id associated with a round
     *
     * @param int $roundId  \Gems round id
     * @return int \Gems survey id
     */
    protected function getSurveyId(int $roundId): int
    {
       $this->_ensureRounds();
       if (isset($this->_rounds[$roundId]['gro_id_survey'])) {
           return $this->_rounds[$roundId]['gro_id_survey'];
       }

       throw new Coding("Requested non existing survey id for round $roundId.");
    }

    /**
     * An array of snippet names for deleting a token.
     *
     * @param \Gems\Tracker\Token $token Allows token status dependent delete snippets
     * @return array of string snippet names
     */
    public function getTokenDeleteSnippetNames(Token $token): array
    {
        return ['Token\\DeleteTrackTokenSnippet', CurrentButtonRowSnippet::class];
    }

    /**
     * An array of snippet names for editing a token.
     *
     * @param \Gems\Tracker\Token $token Allows token status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTokenEditSnippetNames(Token $token): array
    {
        return ['Token\\EditTrackTokenSnippet'];
    }

    /**
     * An array of snippet names for displaying a token
     *
     * @param \Gems\Tracker\Token $token Allows token status dependent show snippets
     * @return array of string snippet names
     */
    public function getTokenShowSnippetNames(Token $token): array
    {
        $output[] = 'Token\\ShowTrackTokenSnippet';

        if ($token->isCompleted() && $this->currentUser?->hasPrivilege('pr.token.answers')) {
            $output[] = 'Tracker\\Answers\\SingleTokenAnswerModelSnippet';
        } elseif ($this->currentUser?->hasPrivilege('pr.project.questions')) {
            $output[] = 'Survey\\SurveyQuestionsSnippet';
        }
        return $output;
    }

    /**
     * The track type of this engine
     *
     * @return string 'T' or 'S'
     */
    public function getTrackType(): string
    {
        return 'T';
    }

    /**
     * Returns the date to use to calculate the ValidFrom if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int|null $prevRoundId Id from round
     * @param \Gems\Tracker\Token $token
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @return ?DateTimeInterface date time or null
     */
    abstract protected function getValidFromDate(string $fieldSource, string $fieldName, int|null $prevRoundId, Token $token, RespondentTrack $respTrack): ?DateTimeInterface;

    /**
     * Returns the date to use to calculate the ValidUntil if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems\Tracker\Token $token
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param ?DateTimeInterface $validFrom The calculated new valid from value
     * @return ?DateTimeInterface date time or null
     */
    abstract protected function getValidUntilDate(string $fieldSource, string $fieldName, int $prevRoundId, Token $token, RespondentTrack $respTrack, ?DateTimeInterface $validFrom = null): ?DateTimeInterface;

    /**
     * True if the user can create this kind of track in TrackMaintenanceAction.
     * False if this type of track is created by specialized user interface actions.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return boolean
     */
    public function isUserCreatable(): bool
    {
        return true;
    }

    /**
     * Updates the model to reflect the values for the current item data
     *
     * @param MetaModelInterface $model The round model
     * @param array $itemData    The current items data
     * @param string $language   (ISO) language string
     * @return bool True if the update changed values (usually by changed selection lists).
     */
    public function updateRoundModelToItem(MetaModelInterface $model, array &$itemData, string $language): bool
    {
        $this->_ensureRounds();

        // Is this the first token?
        $first  = ! $this->getPreviousRoundId($itemData['gro_id_round'], $itemData['gro_id_order']);

        // Update the current round data
        if (isset($this->_rounds[$itemData['gro_id_round']])) {
            $this->_rounds[$itemData['gro_id_round']] = $itemData + $this->_rounds[$itemData['gro_id_round']];
        } else {
            $this->_rounds[$itemData['gro_id_round']] = $itemData;
        }

        // Default result
        $result = false;

        // VALID AFTER DATE

        if (! $this->_sourceUsesSurvey($itemData['gro_valid_after_source'])) {
            $model->del('gro_valid_after_id', 'label');
        } else {
            // Survey list is independent of the actual chosen source, but not
            // vice versa. So we have to set it now.
            $result = $this->applySurveyListValidAfter($model, $itemData) || $result;
        }

        // Set allowed after sources
        $result = $this->_applyOptions($model, 'gro_valid_after_source', $this->getSourceList(true, $first), $itemData) || $result;

        // Set the after date fields that can be chosen for the current values
        $result = $this->applyDatesValidAfter($model, $itemData, $language) || $result;

        // VALID FOR DATE

        // Display used survey only when appropriate
        if (! $this->_sourceUsesSurvey($itemData['gro_valid_for_source'])) {
            $model->del('gro_valid_for_id', 'label');
        } else {
            // Survey list is indepedent of the actual chosen source, but not
            // vice versa. So we have to set it now.
            $result = $this->applySurveyListValidFor($model, $itemData) || $result;
        }

        // Set allowed for sources
        $result = $this->_applyOptions($model, 'gro_valid_for_source', $this->getSourceList(false, $first), $itemData) || $result;

        // Set the for date fields that can be chosen for the current values
        $result = $this->applyDatesValidFor($model, $itemData, $language) || $result;

        // Apply respondent relation settings
        //$result = $this->applyRespondentRelation($model, $itemData) || $result;
        $this->applyRespondentRelation($model, $itemData);

        //$result = $this->applyOrganizationRounds($model, $itemData) || $result;
        $this->applyOrganizationRounds($model, $itemData);

        return $result;
    }
}
