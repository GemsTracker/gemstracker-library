<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Date\Period;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

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
abstract class Gems_Tracker_Engine_StepEngineAbstract extends \Gems_Tracker_Engine_TrackEngineAbstract
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

    /**
     *
     * @var string Class name for creating the round model.
     */
    protected $_roundModelClass = 'MUtil_Model_TableModel';

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Helper function for default handling of multi options value sets
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string $fieldName
     * @param array $options
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function _applyOptions(\MUtil_Model_ModelAbstract $model, $fieldName, array $options, array &$itemData)
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
    protected function _sourceUsesSurvey($source)
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
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param string $language   (ISO) language string
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applyDatesValidAfter(\MUtil_Model_ModelAbstract $model, array &$itemData, $language)
    {
        // Set the after date fields that can be chosen for the current values
        $dateOptions = $this->getDateOptionsFor($itemData['gro_valid_after_source'], $itemData['gro_valid_after_id'], $language, true);

        return $this->_applyOptions($model, 'gro_valid_after_field', $dateOptions, $itemData);
    }

    /**
     * Set the for dates to be listed for this item and the way they are displayed (if at all)
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param string $language   (ISO) language string
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applyDatesValidFor(\MUtil_Model_ModelAbstract $model, array &$itemData, $language)
    {
        $dateOptions = $this->getDateOptionsFor($itemData['gro_valid_for_source'], $itemData['gro_valid_for_id'], $language, true);

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

    /**
     * Apply respondent relation settings to the round model
     *
     * For respondent surveys, we allow to set a relation, with possible choices:
     *
     *  null => the respondent
     *  0    => undefined (specifiy when assigning)
     *  >0   => the id of the track field of type relation to use
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     *
     * @return boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applyRespondentRelation(\MUtil_Model_ModelAbstract $model, array &$itemData)
    {
        $model->set('gro_id_survey', 'onchange', 'this.form.submit();');
        if (!empty($itemData['gro_id_survey']) && $model->has('gro_id_relationfield')) {
            $forStaff = $this->tracker->getSurvey($itemData['gro_id_survey'])->isTakenByStaff();
            if (!$forStaff) {
                $empty = array('-1' => $this->_('Patient'));

                $relations = $this->getRespondentRelationFields();
                if (!empty($relations)) {
                    $relations = $empty + $relations;
                    $model->set('gro_id_relationfield', 'label', $this->_('Assigned to'), 'multiOptions', $relations, 'order', 25);
                }
                $model->del('ggp_name');
            } else {
                $model->set('ggp_name', 'label', $this->translateAdapter->_('Assigned to'), 'elementClass', 'Exhibitor', 'order', 25);
                $model->set('gro_id_relationfield', 'elementClass', 'hidden');

                $itemData['ggp_name'] = $this->db->fetchOne('select ggp_name from gems__groups join gems__surveys on ggp_id_group = gsu_id_primary_group and gsu_id_survey = ?', $itemData['gro_id_survey']);
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
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    abstract protected function applySurveyListValidAfter(\MUtil_Model_ModelAbstract $model, array &$itemData);

    /**
     * Set the surveys to be listed as valid for choices for this item and the way they are displayed (if at all)
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    abstract protected function applySurveyListValidFor(\MUtil_Model_ModelAbstract $model, array &$itemData);

    /**
     *
     * @param \MUtil_Date $startDate
     * @param string $type
     * @param int $period
     * @return \MUtil_Date
     */
    protected function calculateFromDate($startDate, $type, $period)
    {
        return Period::applyPeriod($startDate, $type, $period);
    }

    /**
     *
     * @param \MUtil_Date $startDate
     * @param string $type
     * @param int $period
     * @return \MUtil_Date
     */
    protected function calculateUntilDate($startDate, $type, $period)
    {
        $date = $this->calculateFromDate($startDate, $type, $period);

        if ($date instanceof \MUtil_Date) {
            if (Period::isDateType($type)) {
                // Make sure day based units are valid until the end of the day.
                $date->setTimeToDayEnd();
            }
            return $date;
        }
    }

    /**
     * Check if the token should be enabled / disabled due to conditions
     *
     * @param \Gems_Tracker_Token $token
     * @param array $round
     * @param int   $userId Id of the user who takes the action (for logging)
     * @param \Gems_Tracker_RespondentTrack Current respondent track
     * @return int The number of tokens changed by this code
     */
    protected function checkTokenCondition(\Gems_Tracker_Token $token, $round, $userId, \Gems_Tracker_RespondentTrack $respTrack)
    {
        $skipCode = $this->util->getReceptionCodeLibrary()->getSkipString();

        // Only if we have a condition, the token is not yet completed and
        // receptioncode is ok or skip we evaluate the condition
        if (empty($round['gro_condition']) ||
            $token->isCompleted() ||
            !($token->getReceptionCode()->isSuccess() || $token->getReceptionCode()->getCode() == $skipCode)) {

            return 0;
        }

        $changed   = 0;
        $condition = $this->loader->getConditions()->loadCondition($round['gro_condition']);
        $newStatus = $condition->isRoundValid($token);
        $oldStatus = $token->getReceptionCode()->isSuccess();

        if ($newStatus !== $oldStatus) {
            $changed = 1;
            if ($newStatus == false) {
                $message = $this->_('Skipped by condition %s: %s');
                $newCode = $skipCode;
            } else {
                $message = $this->_('Activated by condition %s: %s');
                $newCode = $this->util->getReceptionCodeLibrary()->getOKString();
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
     * @param \GemS_Tracker_Token $token
     * @param array $round
     * @param int   $userId Id of the user who takes the action (for logging)
     * @param \Gems_Tracker_RespondentTrack Current respondent track
     * @return int 1 if the token has changed
     */
    protected function checkTokenDates($token, $round, $userId, \Gems_Tracker_RespondentTrack $respTrack)
    {
        $skipCode = $this->util->getReceptionCodeLibrary()->getSkipString();

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
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param \Gems_Tracker_Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems_Tracker_Token $skipToken Optional token to skip in the recalculation
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(\Gems_Tracker_RespondentTrack $respTrack, \Gems_Tracker_Token $startToken, $userId, \Gems_Tracker_Token $skipToken = null)
    {
        // Make sure the rounds are loaded
        $this->_ensureRounds();

        // Make sure the tokens are loaded and linked.
        $respTrack->getTokens();

        // Go
        $changed = 0;
        $token = $startToken;
        while ($token) {
            // \MUtil_Echo::track($token->getTokenId());
            //Only process the token when linked to a round
            $round   = false;
            $changes = 0;
            if (array_key_exists($token->getRoundId(), $this->_rounds)) {
                $round = $this->_rounds[$token->getRoundId()];
            }

            if ($round && $token !== $skipToken) {
                $changes = $this->checkTokenDates($token, $round, $userId, $respTrack);
                $changes += $this->checkTokenCondition($token, $round, $userId, $respTrack);
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
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFromStart(\Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        $token = $respTrack->getFirstToken();
        if ($token instanceof \Gems_Tracker_Token) {
            return $this->checkTokensFrom($respTrack, $respTrack->getFirstToken(), $userId);
        } else {
            return 0;
        }
    }

    /**
     * Changes the display of gro_valid_[after|for]_field into something readable
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The value to use
     */
    public function displayDateCalculation($value, $new, $name, array $context = array())
    {
        $fieldBase = substr($name, 0, -5);  // Strip field
        $validAfter = (bool) strpos($fieldBase, 'after');

        // When always valid, just return nothing
        if ($context[$fieldBase . 'source'] == self::NO_TABLE) return '';

        $fields = $this->getDateOptionsFor(
                $context[$fieldBase . 'source'],
                $context[$fieldBase . 'id'],
                $this->locale->getLanguage(),
                $validAfter
                );

        if (isset($fields[$context[$fieldBase . 'field']])) {
            $field = $fields[$context[$fieldBase . 'field']];
        } else {
            $field = $context[$fieldBase . 'field'];
        }

        if ($context[$fieldBase . 'length'] > 0) {
            $format = $this->_('%s plus %s %s');
        } elseif($context[$fieldBase . 'length'] < 0) {
            $format = $this->_('%s minus %s %s');
        } else {
            $format = $this->_('%s');
        }

        $units = $this->util->getTranslated()->getPeriodUnits();
        if (isset($units[$context[$fieldBase . 'unit']])) {
            $unit = $units[$context[$fieldBase . 'unit']];
        } else {
            $unit = $context[$fieldBase . 'unit'];
        }

        // \MUtil_Echo::track(func_get_args());
        return sprintf($format, $field, abs($context[$fieldBase . 'length']), $unit);
    }

    /**
     * Changes the display of gro_valid_[for|after]_id into something readable
     *
     * Makes it empty when not applicable
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The value to use
     */
    public function displayRoundId($value, $new, $name, array $context = array())
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
    public function getAnswerSnippetNames()
    {
        return array('Tracker_Answers_TrackAnswersModelSnippet');
    }

    /**
     * Returns the date fields selectable using the current source
     *
     * @param string $source The date list source as defined by this object
     * @param int $roundId  Gems round id
     * @param string $language   (ISO) language string
     * @param boolean $validAfter True if it concenrs _valid_after_ dates
     * @return type
     */
    protected function getDateOptionsFor($sourceType, $roundId, $language, $validAfter)
    {
        switch ($sourceType) {
            case self::NO_TABLE:
                return array();

            case self::ANSWER_TABLE:
                if (! isset($this->_rounds[$roundId], $this->_rounds[$roundId]['gro_id_survey'])) {
                    return array();
                }
                $surveyId = $this->_rounds[$roundId]['gro_id_survey'];
                $survey = $this->tracker->getSurvey($surveyId);
                return $survey->getDatesList($language);

            case self::APPOINTMENT_TABLE:
                return $this->_fieldsDefinition->getFieldLabelsOfType(FieldsDefinition::TYPE_APPOINTMENT);

            case self::RESPONDENT_TRACK_TABLE:
                $results = array(
                    'gr2t_start_date' => $this->_('Track start'),
                    'gr2t_end_date'   => $this->_('Track end'),
                    // 'gr2t_created'    => $this->_('Track created'),
                );

                return $results + $this->_fieldsDefinition->getFieldLabelsOfType(array(
                    FieldsDefinition::TYPE_DATE,
                    FieldsDefinition::TYPE_DATETIME,
                    ));

            case self::RESPONDENT_TABLE:
                return [
                    'grs_birthday' => $this->_('Birthday'),
                    'gr2o_created' => $this->_('Respondent created'),
                    /*'gr2o_changed' => $this->_('Respondent changed'),*/
                    ];

            case self::TOKEN_TABLE:
                return array(
                    'gto_valid_from'      => $this->_('Valid from'),
                    'gto_valid_until'     => $this->_('Valid until'),
                    'gto_start_time'      => $this->_('Start time'),
                    'gto_completion_time' => $this->_('Completion date'),
                    );

        }
    }

    /**
     * Get all respondent relation fields
     *
     * Returns an array of field id => field name
     *
     * @return array
     */
    public function getRespondentRelationFields() {
        $fields = array();
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
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \MUtil_Model_ModelAbstract
     */
    public function getRoundModel($detailed, $action)
    {
        $model = parent::getRoundModel($detailed, $action);

        // Add information about surveys and groups
        $model->addLeftTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));
        $model->addLeftTable('gems__groups', array('gsu_id_primary_group' => 'ggp_id_group'));
        $model->addLeftTable('gems__track_fields', array('gro_id_relationfield = gtf_id_field'), 'gtf', false);

        $model->addColumn(new \Zend_Db_Expr('COALESCE(gtf_field_name, ggp_name)'), 'ggp_name');
        $model->set('ggp_name', 'label', $this->_('Assigned to'));

        // Reset display order to class specific order
        $model->resetOrder();
        if (! $detailed) {
            $model->set('gro_id_order');
        }
        $model->set('gro_id_track');
        $model->set('gro_id_survey');
        $model->set('gro_round_description');
        if ($detailed) {
            $model->set('gro_id_order');
        }
        $model->set('gro_icon_file');

        // Calculate valid from
        if ($detailed) {
            $html = \MUtil_Html::create()->h4($this->_('Valid from calculation'));
            $model->set('valid_after',
                    'default', $html,
                    'label', ' ',
                    'elementClass', 'html',
                    'value', $html
                    );
        }
        $model->set('gro_valid_after_source',
                'label', $this->_('Date source'),
                'default', self::TOKEN_TABLE,
                'elementClass', 'Radio',
                'escape', false,
                'required', true,
                'onchange', 'this.form.submit();',
                'multiOptions', $this->getSourceList(true, false, false)
                );
        $model->set('gro_valid_after_id',
                'label', $this->_('Round used'),
                'onchange', 'this.form.submit();'
                );

        if ($detailed) {
            $periodUnits = $this->util->getTranslated()->getPeriodUnits();

            $model->set('gro_valid_after_field',
                    'label', $this->_('Date used'),
                    'default', 'gto_valid_from',
                    'onchange', 'this.form.submit();'
                    );
            $model->set('gro_valid_after_length',
                    'label', $this->_('Add to date'),
                    'description', $this->_('Can be negative'),
                    'required', false,
                    'filter', 'Int'
                    );
            $model->set('gro_valid_after_unit',
                    'label', $this->_('Add to date unit'),
                    'multiOptions', $periodUnits
                    );
        } else {
            $model->set('gro_valid_after_source',
                    'label', $this->_('Source'),
                    'tableDisplay', 'small'
                    );
            $model->set('gro_valid_after_id', 'label', $this->_('Round'),
                    'multiOptions', $this->getRoundTranslations(),
                    'tableDisplay', 'small'
                    );
            $model->setOnLoad('gro_valid_after_id', array($this, 'displayRoundId'));
            $model->set('gro_valid_after_field',
                    'label', $this->_('Date calculation'),
                    'tableHeaderDisplay', 'small'
                    );
            $model->setOnLoad('gro_valid_after_field', array($this, 'displayDateCalculation'));
            $model->set('gro_valid_after_length');
            $model->set('gro_valid_after_unit');
        }

        if ($detailed) {
            // Calculate valid until
            $html = \MUtil_Html::create()->h4($this->_('Valid for calculation'));
            $model->set('valid_for',
                    'label', ' ',
                    'default', $html,
                    'elementClass', 'html',
                    'value', $html
                    );
        }

        $model->set('gro_valid_for_source',
                'label', $this->_('Date source'),
                'default', self::TOKEN_TABLE,
                'elementClass', 'Radio',
                'escape', false,
                'required', true,
                'onchange', 'this.form.submit();',
                'multiOptions', $this->getSourceList(false, false, false)
                );
        $model->set('gro_valid_for_id',
                'label', $this->_('Round used'),
                'default', '',
                'onchange', 'this.form.submit();'
                );

        if ($detailed) {
            $model->set('gro_valid_for_field',
                    'label', $this->_('Date used'),
                    'default', 'gto_valid_from',
                    'onchange', 'this.form.submit();'
                    );
            $model->set('gro_valid_for_length',
                    'label', $this->_('Add to date'),
                    'description', $this->_('Can be negative'),
                    'required', false,
                    'default', 2,
                    'filter', 'Int'
                    );
            $model->set('gro_valid_for_unit',
                    'label', $this->_('Add to date unit'),
                    'multiOptions', $periodUnits
                    );

            // Calculate valid until
            $html = \MUtil_Html::create()->h4($this->_('Validity calculation'));
            $model->set('valid_cond',
                        'label', ' ',
                        'default', $html,
                        'elementClass', 'html',
                        'value', $html
            );

            // Continue with last round level items
            $model->set('gro_condition');
            $model->set('condition_display');
            $model->set('gro_active');
            $model->set('gro_changed_event');
        } else {
            $model->set('gro_valid_for_source',
                    'label', $this->_('Source'),
                    'tableDisplay', 'small'
                    );
            $model->set('gro_valid_for_id',
                    'label', $this->_('Round'),
                    'multiOptions', $this->getRoundTranslations(),
                    'tableDisplay', 'small'
                    );
            $model->setOnLoad('gro_valid_for_id', array($this, 'displayRoundId'));
            $model->set('gro_valid_for_field',
                    'label', $this->_('Date calculation'),
                    'tableHeaderDisplay', 'small'
                    );
            $model->setOnLoad('gro_valid_for_field', array($this, 'displayDateCalculation'));
            $model->set('gro_valid_for_length');
            $model->set('gro_valid_for_unit');
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

        return \MUtil_Ra::column('gro_id_order', $this->_rounds);
    }

    /**
     * Returns the source choices in an array.
     *
     * @param boolean $validAfter True if it concerns _valid_after_ dates
     * @param boolean $firstRound List for first round
     * @param boolean $detailed   Return extended info
     * @return array source_name => label
     */
    protected function getSourceList($validAfter, $firstRound, $detailed = true)
    {
        if (! ($validAfter || $this->project->isValidUntilRequired())) {
            $results[self::NO_TABLE] = array($this->_('Does not expire'));
        }
        if (! ($validAfter && $firstRound)) {
            $results[self::ANSWER_TABLE] = array($this->_('Answers'), $this->_('Use an answer from a survey.'));
        }
        if ($this->_fieldsDefinition->hasAppointmentFields()) {
            $results[self::APPOINTMENT_TABLE] = array(
                $this->_('Appointment'),
                $this->_('Use an appointment linked to this track.'),
                );
        }
        if (! ($validAfter && $firstRound)) {
            $results[self::TOKEN_TABLE]  = array($this->_('Token'), $this->_('Use a standard token date.'));
        }
        $results[self::RESPONDENT_TRACK_TABLE] = array($this->_('Track'), $this->_('Use a track level date.'));
        $results[self::RESPONDENT_TABLE] = array($this->_('Respondent'), $this->_('Use a respondent level date.'));

        if ($detailed) {
            foreach ($results as $key => $value) {
                if (is_array($value)) {
                    $results[$key] = \MUtil_Html::raw(sprintf('<strong>%s</strong> %s', reset($value), next($value)));
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
     * @param int $roundId  Gems round id
     * @return int Gems survey id
     */
    protected function getSurveyId($roundId)
    {
       $this->_ensureRounds();
       if (isset($this->_rounds[$roundId]['gro_id_survey'])) {
           return $this->_rounds[$roundId]['gro_id_survey'];
       }

       throw new \Gems_Exception_Coding("Requested non existing survey id for round $roundId.");
    }

    /**
     * An array of snippet names for deleting a token.
     *
     * @param \Gems_Tracker_Token $token Allows token status dependent delete snippets
     * @return array of string snippet names
     */
    public function getTokenDeleteSnippetNames(\Gems_Tracker_Token $token)
    {
        return array('Token\\DeleteTrackTokenSnippet');
    }

    /**
     * An array of snippet names for editing a token.
     *
     * @param \Gems_Tracker_Token $token Allows token status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTokenEditSnippetNames(\Gems_Tracker_Token $token)
    {
        return array('Token\\EditTrackTokenSnippet');
    }

    /**
     * An array of snippet names for displaying a token
     *
     * @param \Gems_Tracker_Token $token Allows token status dependent show snippets
     * @return array of string snippet names
     */
    public function getTokenShowSnippetNames(\Gems_Tracker_Token $token)
    {
        $output[] = 'Token\\ShowTrackTokenSnippet';

        if ($token->isCompleted() && $this->currentUser->hasPrivilege('pr.token.answers')) {
            $output[] = 'Tracker_Answers_SingleTokenAnswerModelSnippet';
        } elseif ($this->currentUser->hasPrivilege('pr.project.questions')) {
            $output[] = 'Survey\\SurveyQuestionsSnippet';
        }
        return $output;
    }

    /**
     * An array of snippet names for deleting a track.
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack Allows track status dependent edit snippets
     * @return array of string snippet names
     * @deprecated since version 1.7.1 Snippets defined TrackAction
     */
    public function getTrackDeleteSnippetNames(\Gems_Tracker_RespondentTrack $respTrack)
    {
        return array('Tracker\\DeleteTrackSnippet', 'Tracker\\TrackTokenOverviewSnippet');
    }

    /**
     * An array of snippet names for editing a track.
     *
     * @return array of string snippet names
     * @deprecated since version 1.7.1 Snippets defined TrackAction
     */
    public function getTrackCreateSnippetNames()
    {
        return array(
            'Tracker\\ShowTrackUsageSnippet',
            'Tracker\\EditTrackSnippet',
            'Tracker\\TrackUsageTextDetailsSnippet',
            'Tracker\\TrackSurveyOverviewSnippet',
            );
    }

    /**
     * An array of snippet names for editing a track.
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack Allows track status dependent edit snippets
     * @return array of string snippet names
     * @deprecated since version 1.7.1 Snippets defined TrackAction
     */
    public function getTrackEditSnippetNames(\Gems_Tracker_RespondentTrack $respTrack)
    {
        return array(
            'Tracker\\ShowTrackUsageSnippet',
            'Tracker\\EditTrackSnippet',
            'Tracker\\TrackUsageTextDetailsSnippet',
            'Tracker\\TrackSurveyOverviewSnippet',
            );
    }

    /**
     * The track type of this engine
     *
     * @return string 'T' or 'S'
     */
    public function getTrackType()
    {
        return 'T';
    }

    /**
     * Returns the date to use to calculate the ValidFrom if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems_Tracker_Token $token
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @return \MUtil_Date date time or null
     */
    abstract protected function getValidFromDate($fieldSource, $fieldName, $prevRoundId, \Gems_Tracker_Token $token, \Gems_Tracker_RespondentTrack $respTrack);

    /**
     * Returns the date to use to calculate the ValidUntil if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems_Tracker_Token $token
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @param \MUtil_Date $validFrom The calculated new valid from value
     * @return \MUtil_Date date time or null
     */
    abstract protected function getValidUntilDate($fieldSource, $fieldName, $prevRoundId, \Gems_Tracker_Token $token, \Gems_Tracker_RespondentTrack $respTrack, $validFrom);

    /**
     * True if the user can create this kind of track in TrackMaintenanceAction.
     * False if this type of track is created by specialized user interface actions.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return boolean
     */
    public function isUserCreatable()
    {
        return true;
    }

    /**
     * The logic to set the display of the valid_X_field date list field.
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param string $fieldName The valid_X_field to set
     * @param string $source The date list source as defined by this object
     * @param int $roundId Optional a round id
     * @param string $language   (ISO) language string
     * @param boolean $validAfter True if it concerns _valid_after_ dates
     */
    protected function setDateListFor(\MUtil_Model_ModelAbstract $model, $fieldName, $source, $roundId, $language, $validAfter)
    {
        $dateOptions = $this->getDateOptionsFor($source, $roundId, $language, $validAfter);

        switch (count($dateOptions)) {
            case 0:
                $model->del($fieldName, 'label');
                break;
            case 1:
                $model->set($fieldName, 'elementClass', 'exhibitor');
                // Intentional fall through
            default:
                $model->set($fieldName, 'multiOptions', $dateOptions);
                break;
        }
    }

    /**
     * Updates the model to reflect the values for the current item data
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param string $language   (ISO) language string
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    public function updateRoundModelToItem(\MUtil_Model_ModelAbstract $model, array &$itemData, $language)
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
        $result = $this->applyRespondentRelation($model, $itemData) || $result;

        return $result;
    }
}
