<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Log\LogHelper;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Ddl\AlterTable;
use Laminas\Db\Sql\Ddl\Column\Varchar;
use MUtil\Model;

/**
 * LimeSurvey3m00Database is a Source interface that enables the use of LimeSurvey 3.x
 * installation as survey/answer source for \Gems projects.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class LimeSurvey3m00Database extends SourceAbstract
{
    const CACHE_TOKEN_INFO = 'tokenInfo';

    const LS_DB_COMPLETION_FORMAT = 'Y-m-d H:i';
    const LS_DB_DATE_FORMAT       = 'Y-m-d';
    const LS_DB_DATETIME_FORMAT   = 'Y-m-d H:i:s';

    const QUESTIONS_TABLE    = 'questions';
    const SURVEY_TABLE       = 'survey_';
    const SURVEYS_LANG_TABLE = 'surveys_languagesettings';
    const SURVEYS_TABLE      = 'surveys';
    const TOKEN_TABLE        = 'tokens_';

    /**
     * @var array meta data fields that are included in a survey table
     */
    public static $metaFields = [
        'id',
        'submitdate',
        'lastpage',
        'startlanguage',
        'token',
        'datestamp',
        'startdate',
        'seed',
    ];

    /**
     * LS 1.91: The field private = y was changed to anonymized = y
     *
     * @var string The LS version dependent field name for anonymized surveys
     */
    protected $_anonymizedField = 'anonymized';

    /**
     *
     * @var string The field that holds the token attribute descriptions in the surveys table
     */
    protected $_attributeDescriptionsField = 'attributedescriptions';

    /**
     * A map containing attributename => databasefieldname mappings
     *
     * Should contain maps for respondentid, organizationid and consentcode.
     *
     * @var array
     */
    protected $_attributeMap = array(
        'respondentid'   => 'attribute_1',
        'organizationid' => 'attribute_2',
        'consentcode'    => 'attribute_3',
        'resptrackid'    => 'attribute_4');

    /**
     *
     * @var array of \Gems\Tracker\Source\LimeSurvey3m00FieldMap
     */
    private $_fieldMaps;

    /**
     *
     * @var array of string
     */
    private $_languageMap;

    /**
     * The default text length attribute fields should have.
     *
     * @var int
     */
    protected $attributeSize = 255;

    /**
     *
     * @var \Gems\Cache\HelperAdapter
     */
    protected $cache;

    /**
     * @var array
     */
    protected $config;

    /**
     *
     * @var string class name for creating field maps
     */
    protected $fieldMapClass = '\\Gems\\Tracker\\Source\\LimeSurvey3m00FieldMap';

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Checks the return URI in LimeSurvey and sets it to the correct one when needed
     *
     * @see checkSurvey()
     *
     * @param string $sourceSurveyId
     * @param \Gems\Tracker\Survey $survey
     * @param array $messages
     */
    protected function _checkReturnURI($sourceSurveyId, \Gems\Tracker\Survey $survey, array &$messages)
    {
        $lsSurvLang = $this->_getSurveyLanguagesTableName();
        $sql = 'SELECT surveyls_language FROM ' . $lsSurvLang . ' WHERE surveyls_survey_id = ?';

        $lsAdapter = $this->getSourceDatabase();
        $lsResultFetcher = $this->getSourceResultFetcher();

        $languages = $lsResultFetcher->fetchAll($sql, [$sourceSurveyId]);
        $langChanges = 0;
        $sql = new Sql($lsAdapter);
        foreach ($languages as $language)
        {
            $update = $sql->update($lsSurvLang)
                ->set([
                    'surveyls_urldescription' => $this->_getReturnURIDescription($language['surveyls_language']),
                ])->where([
                    'surveyls_survey_id' => $sourceSurveyId,
                    'surveyls_language'  => $language,
                ]);
            $result = $sql->prepareStatementForSqlObject($update)->execute();
            $langChanges = $langChanges + $result->getAffectedRows();
        }

        if ($langChanges > 0) {
            $messages[] = sprintf($this->_('The description of the exit url description was changed for %s languages in survey \'%s\'.'), $langChanges, $survey->getName());
        }
    }

    /**
     * Check a token table for any changes needed by this version.
     *
     * @param array $tokenTable
     * @return array Fieldname => change field commands
     */
    protected function _checkTokenTable(array $tokenTable)
    {
        $missingFields = array();

        $tokenLength = $this->_extractFieldLength($tokenTable['token']['Type']);
        $token_library = $this->tracker->getTokenLibrary();
        if ($tokenLength < $token_library->getLength()) {
            $tokenLength = $token_library->getLength();
            $missingFields['token'] = 'CHANGE COLUMN `token` `token` varchar(' . $tokenLength .
                    ") CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL";
        }

        foreach ($this->_attributeMap as $name => $field) {
            if (! isset($tokenTable[$field])) {
                $missingFields[$field] = 'ADD ' . $field . ' varchar(' . $this->attributeSize .
                        ") CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
            } else {
                $attrLength = $this->_extractFieldLength($tokenTable[$field]['Type']);
                if ($attrLength < $this->attributeSize) {
                    $missingFields[$field] = "CHANGE COLUMN `$field` `$field` varchar(" .
                            $this->attributeSize .
                            ") CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL";
                }
            }
        }

        // Added in LS 1.91
        if (! isset($tokenTable['usesleft'])) {
            $missingFields['usesleft'] = "ADD `usesleft` INT( 11 ) NULL DEFAULT '1' AFTER `completed`";
        }

        // Added in LS 2.00
        if (! isset($tokenTable['participant_id'])) {
            $missingFields['participant_id'] = "ADD participant_id varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL";
        }
        if (! isset($tokenTable['blacklisted'])) {
            $missingFields['blacklisted'] = "ADD blacklisted varchar(17) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL";
        }

        return $missingFields;
    }

    /**
     *
     * @param string $typeDescr E.g. int(11) or varchar(36)
     * @return int In case 11 or 36
     */
    private function _extractFieldLength($typeDescr): int
    {
        $lengths = array();
        if (preg_match('/\(([^\)]+)\)/', $typeDescr, $lengths)) {
            return intval($lengths[1]);
        }

        return $this->attributeSize;    // When type is text there is no size
    }

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(\Gems\Tracker\Token $token)
    {
        $values[$this->_attributeMap['respondentid']]   =
                substr($token->getRespondentId(), 0, $this->attributeSize);
        $values[$this->_attributeMap['organizationid']] =
                substr($token->getOrganizationId(), 0, $this->attributeSize);
        $values[$this->_attributeMap['consentcode']]    =
                substr($token->getConsentCode(), 0, $this->attributeSize);
        $values[$this->_attributeMap['resptrackid']]    =
                substr($token->getRespondentTrackId(), 0, $this->attributeSize);

        // Added in LS 1.91
        // Not really an attribute, but it is the best place to set this
        $values['usesleft'] = $token->isCompleted() ? 0 : 1;

        // Added in LS 2.00
        // Not really attributes, but they need a value
        $values['participant_id'] = '';
        $values['blacklisted']    = '';

        return $values;
    }

    /**
     * Filters an answers array, return only those fields that where answered by the user.
     *
     * @param int $sourceSurveyId Survey ID
     * @param array $answers
     * @return array
     */
    protected function _filterAnswersOnly($sourceSurveyId, array $answers)
    {
        $s = $sourceSurveyId . 'X';
        $l = strlen($s);

        $results = array();
        foreach ($answers as $key => $value) {
            if (substr($key, 0, $l) == $s) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Return a fieldmap object
     *
     * @param int $sourceSurveyId Survey ID
     * @param string $language      Optional (ISO) Language, uses default language for survey when null
     * @return \Gems\Tracker\Source\LimeSurvey3m00FieldMap
     */
    protected function _getFieldMap($sourceSurveyId, $language = null)
    {
        $language = $this->_getLanguage($sourceSurveyId, $language);
        // \MUtil\EchoOut\EchoOut::track($language, $sourceSurveyId);

        if (! isset($this->_fieldMaps[$sourceSurveyId][$language])) {
            $className = $this->fieldMapClass;
            $this->_fieldMaps[$sourceSurveyId][$language] = new $className(
                    $sourceSurveyId,
                    $language,
                    $this->getSourceDatabase(),
                    $this->translate,
                    $this->addDatabasePrefix(''),
                    $this->cache,
                    $this->getId()
            );
        }

        return $this->_fieldMaps[$sourceSurveyId][$language];
    }

    /**
     * Returns the langauge to use for the survey when this language is specified.
     *
     * Uses the requested language if it exists for the survey, the default language for the survey otherwise
     *
     * @param int $sourceSurveyId Survey ID
     * @param string $language       (ISO) Language
     * @return string (ISO) Language
     */
    protected function _getLanguage($sourceSurveyId, $language)
    {
        if (! is_string($language)) {
            $language = (string) $language;
        }

        if (! isset($this->_languageMap[$sourceSurveyId][$language])) {
            if ($language && $this->_isLanguage($sourceSurveyId, $language)) {
                $this->_languageMap[$sourceSurveyId][$language] = $language;
            } else {
                $lsResultFetcher = $this->getSourceResultFetcher();

                $sql = 'SELECT language
                    FROM ' . $this->_getSurveysTableName() . '
                    WHERE sid = ?';

                $this->_languageMap[$sourceSurveyId][$language] = $lsResultFetcher->fetchOne($sql, [$sourceSurveyId]);
            }
        }

        return $this->_languageMap[$sourceSurveyId][$language];
    }

    /**
     * Get the return URI to return from LimeSurvey to GemsTracker
     *
     * @param \Gems\User\Organization|null $organization
     * @return string
     */
    protected function _getReturnURI(\Gems\User\Organization $organization = null)
    {
        if ($organization) {
            $currentUrl = $organization->getPreferredSiteUrl();
        } else {
            $currentUrl = $this->util->getCurrentURI();
        }
        return $currentUrl . '/ask/return/{TOKEN}';
    }

    /**
     * Get the return URI description to set in LimeSurvey
     *
     * @param string $language
     * @return string
     */
    protected function _getReturnURIDescription($language)
    {
        $message = $this->translate->_('Back', [], null, $language);
        if (isset($this->config['app']['name'])) {
            $message = sprintf($this->translate->_('Back to %s', [], null, $language), $this->config['app']['name']);
        }

        return $message;
    }

    /**
     * Looks up the LimeSurvey Survey Id
     *
     * @param int $surveyId
     * @return int
     */
    protected function _getSid($surveyId)
    {
        return $this->tracker->getSurvey($surveyId)->getSourceSurveyId();
    }

    /**
     * Returns all surveys for synchronization
     *
     * @return array of sourceId values or false
     */
    protected function _getSourceSurveysForSynchronisation()
    {
        // Surveys in LS
        $lsResultFetcher = $this->getSourceResultFetcher();

        $select = $lsResultFetcher->getSelect();
        $select->from($this->_getSurveysTableName())
                ->columns(['sid'])
                ->order('sid');

        return $lsResultFetcher->fetchCol($select);
    }

    /**
     * The survey languages table contains the survey level texts per survey
     *
     * @return string Name of survey languages table
     */
    protected function _getSurveyLanguagesTableName()
    {
        return $this->addDatabasePrefix(self::SURVEYS_LANG_TABLE);
    }

    /**
     * There exists a survey table for each active survey. The table contains the answers to the survey
     *
     * @param int $sourceSurveyId Survey ID
     * @return string Name of survey table for this survey
     */
    protected function _getSurveyTableName($sourceSurveyId)
    {
        return $this->addDatabasePrefix(self::SURVEY_TABLE . $sourceSurveyId);
    }

    /**
     * The survey table contains one row per each survey in LS
     *
     * @return string Name of survey table
     */
    protected function _getSurveysTableName()
    {
        return $this->addDatabasePrefix(self::SURVEYS_TABLE);
    }

    /**
     * Replaces hyphen with underscore so LimeSurvey won't choke on it
     *
     * @param string $tokenId
     * @param boolean $reverse  Reverse the action to go from limesurvey to GemsTracker token (default is false)
     * @return string
     */
    protected function _getToken($tokenId, $reverse = false)
    {
        $tokenId = strtolower($tokenId);
        if ($reverse) {
            return strtr($tokenId, '_', '-');
        } else {
            return strtr($tokenId, '-', '_');
        }
    }

    /**
     * There exists a token table for each active survey with tokens.
     *
     * @param int $sourceSurveyId Survey ID
     * @return string Name of token table for this survey
     */
    protected function _getTokenTableName($sourceSurveyId)
    {
        return $this->addDatabasePrefix(self::TOKEN_TABLE . $sourceSurveyId);
    }

    /**
     * Check if the specified language is available in Lime Survey
     *
     * @param int $sourceSurveyId Survey ID
     * @param string $language       (ISO) Language
     * @return boolean True when the language is an existing language
     */
    protected function _isLanguage($sourceSurveyId, $language): bool
    {
        if ($language && strlen($language)) {
            // Check for availability of language
            $sql = 'SELECT surveyls_language FROM ' . $this->_getSurveyLanguagesTableName() . ' WHERE surveyls_survey_id = ? AND surveyls_language = ?';
            $lsResultFetcher = $this->getSourceResultFetcher();

            return !is_null($lsResultFetcher->fetchOne($sql, [$sourceSurveyId, $language]));
        }

        return false;
    }

    /**
     * Check if the tableprefix exists in the source database, and change the status of this
     * adapter in the gems_sources table accordingly
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return boolean  True if the source is active
     */
    public function checkSourceActive($userId)
    {
        // The only method to check if it is active is by getting all the tables,
        // since the surveys table may be empty so we just check for existence.
        $sourceDb  = $this->getSourceDatabase();
        $metadata = \Laminas\Db\Metadata\Source\Factory::createSourceFromAdapter($sourceDb);
        $tables    = array_map('strtolower', $metadata->getTableNames());
        $tableName = $this->addDatabasePrefix(self::SURVEYS_TABLE, false); // Get name without database prefix.

        $active = in_array($tableName, $tables);

        $values['gso_active'] = $active ? 1 : 0;
        $values['gso_status'] = $active ? 'Active' : 'Inactive';

        $this->_updateSource($values, $userId);

        return $active;
    }

    /**
     * Survey source synchronization check function
     *
     * @param string $sourceSurveyId
     * @param int $surveyId
     * @param int $userId
     * @return mixed message string or array of messages
     */
    public function checkSurvey($sourceSurveyId, $surveyId, $userId)
    {
        $messages = array();
        $survey   = $this->tracker->getSurvey($surveyId);

        if (null === $sourceSurveyId) {
            // Was removed
            $values['gsu_active'] = 0;
            $values['gsu_surveyor_active'] = 0;
            $values['gsu_status'] = 'Survey was removed from source.';

            if ($survey->saveSurvey($values, $userId)) {
                $messages[] = sprintf($this->_('The \'%s\' survey is no longer active. The survey was removed from LimeSurvey!'), $survey->getName());
            }
        } else {
            $lsAdapter = $this->getSourceDatabase();
            $lsResultFetcher = $this->getSourceResultFetcher();

            // SELECT sid, surveyls_title AS short_title, surveyls_description AS description, active, datestamp, ' . $this->_anonymizedField . '
            $select = $lsResultFetcher->getSelect();
            // 'alloweditaftercompletion' ?
            $select->from($this->_getSurveysTableName())
                    ->columns(['active', 'datestamp', 'language', 'additional_languages', 'autoredirect', 'alloweditaftercompletion', 'allowregister', 'listpublic', 'tokenanswerspersistence', 'expires', $this->_anonymizedField])
                    ->join($this->_getSurveyLanguagesTableName(),
                           'sid = surveyls_survey_id AND language = surveyls_language',
                           ['surveyls_title', 'surveyls_description'])
                    ->where(['sid' => $sourceSurveyId]);
            $lsSurvey = $lsResultFetcher->fetchRow($select);

            $surveyor_title = mb_substr(\MUtil\Html::removeMarkup(html_entity_decode($lsSurvey['surveyls_title'])), 0, 100);
            $surveyor_description = mb_substr(\MUtil\Html::removeMarkup(html_entity_decode($lsSurvey['surveyls_description'])), 0, 100);
            $surveyor_status = '';
            $surveyor_warnings = '';

            // AVAILABLE LANGUAGES
            $surveyor_languages = mb_substr(\MUtil\Html::removeMarkup(html_entity_decode($lsSurvey['language'])), 0, 100);
            $surveyor_additional_languages = mb_substr(\MUtil\Html::removeMarkup(html_entity_decode($lsSurvey['additional_languages'])), 0, 100);
            if ($surveyor_additional_languages) {
                $array = explode(' ', $surveyor_additional_languages);
                foreach ($array as $value) {
                    $surveyor_languages .= ', ';
                    $surveyor_languages .= $value;
                }
            }

            // ANONIMIZATION
            switch ($lsSurvey[$this->_anonymizedField]) {
                case 'Y':
                    $surveyor_status .= 'Uses anonymous answers. ';
                    break;
                case 'N':
                    break;
                default:
                    // This is for the case that $this->_anonymizedField is empty, we show an update statement.
                    // The answers already in the table can only be linked to the response based on the completion time
                    // this requires a manual action as token table only hold minuts while survey table holds seconds
                    // and we might have responses with the same timestamp.
                    $sql = new Sql($lsAdapter);
                    $update = $sql->update($this->_getSurveysTableName())
                            ->set([
                                $this->_anonymizedField => 'N',
                            ])
                            ->where([
                                'sid' => $sourceSurveyId,
                            ]);
                    $sql->prepareStatementForSqlObject($update)->execute();
                    $messages[] = sprintf($this->_("Corrected anonymization for survey '%s'"), $surveyor_title);

                    $table = new AlterTable($this->_getSurveyTableName($sourceSurveyId));
                    $table->addColumn(new Varchar('token', 36));
                    $lsAdapter->query(
                        $sql->buildSqlString($table),
                        $lsAdapter::QUERY_MODE_EXECUTE
                    );
            }

            // DATESTAMP
            if ($lsSurvey['datestamp'] == 'N') {
                $surveyor_status .= 'Not date stamped. ';
            }

            // DATESTAMP
            if ($lsSurvey['tokenanswerspersistence'] == 'N') {
                $surveyor_status .= 'Token-based persistence is disabled. ';
            }

            // IS ACTIVE
            if ($lsSurvey['active'] == 'Y') {
                try {
                    $tokenTable = $lsResultFetcher->fetchAll('SHOW COLUMNS FROM ' . $this->_getTokenTableName($sourceSurveyId));
                } catch (\RuntimeException $e) {
                    $tokenTable = false;
                }

                if ($tokenTable) {
                    $missingFields   = $this->_checkTokenTable($tokenTable);

                    if ($missingFields) {
                        $sql    = "ALTER TABLE " . $this->_getTokenTableName($sourceSurveyId) . " " . implode(', ', $missingFields);
                        $fields = implode($this->_(', '), array_keys($missingFields));
                        // \MUtil\EchoOut\EchoOut::track($missingFields, $sql);
                        try {
                            // FIXME: This is not platform agnostic!
                            $lsAdapter->query($sql, $lsAdapter::QUERY_MODE_EXECUTE);
                            $messages[] = sprintf($this->_("Added to token table '%s' the field(s): %s"), $surveyor_title, $fields);
                        } catch (\RuntimeException $e) {
                            $surveyor_status .= 'Token attributes could not be created. ';
                            $surveyor_status .= $e->getMessage() . ' ';

                            $messages[] = sprintf($this->_("Attribute fields not created for token table for '%s'"), $surveyor_title);
                            $messages[] = sprintf($this->_('Required fields: %s'), $fields);
                            $messages[] = $e->getMessage();

                            // Maximum reporting for this case
                            \MUtil\EchoOut\EchoOut::r($missingFields, 'Missing fields for ' . $surveyor_title);
                            \MUtil\EchoOut\EchoOut::r($e);
                        }
                    }

                    if ($this->fixTokenAttributeDescriptions($sourceSurveyId)) {
                        $messages[] = sprintf($this->_("Updated token attribute descriptions for '%s'"), $surveyor_title);
                    }
                } else {
                    $surveyor_status .= 'No token table created. ';
                }


            } else {
                $surveyor_status .= 'Not active. ';
            }
            $surveyor_active = (0 === strlen($surveyor_status));

            // ADDITIONAL WARNINGS
            if ($lsSurvey['autoredirect'] == 'N') {
                $surveyor_warnings .= "Auto-redirect is disabled. ";
            }

            if ($lsSurvey['alloweditaftercompletion'] == 'Y') {
                $surveyor_warnings .= "Editing after completion is enabled. ";
            }

            if ($lsSurvey['allowregister'] == 'Y') {
                $surveyor_warnings .= "Public registration is enabled. ";
            }

            if ($lsSurvey['listpublic']== 'Y') {
                $surveyor_warnings .= "Public access is enabled. ";
            }

            // Update \Gems
            $values = array();

            if ($survey->exists) {   // Update
                if ($survey->isActiveInSource() != $surveyor_active) {
                    $values['gsu_surveyor_active'] = $surveyor_active ? 1 : 0;

                    $messages[] = sprintf($this->_('The status of the \'%s\' survey has changed.'), $survey->getName());
                }

                // Reset to inactive if the surveyor survey has become inactive.
                if ($survey->isActive() && $surveyor_status) {
                    $values['gsu_active'] = 0;
                    $messages[] = sprintf($this->_('Survey \'%s\' IS NO LONGER ACTIVE!!!'), $survey->getName());
                }

                if (mb_substr($surveyor_status,  0,  127) != (string) $survey->getStatus()) {
                    if ($surveyor_status) {
                        $values['gsu_status'] = mb_substr($surveyor_status,  0,  127);
                        $messages[] = sprintf($this->_('The status of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $values['gsu_status']);
                    } elseif ($survey->getStatus() != 'OK') {
                        $values['gsu_status'] = 'OK';
                        $messages[] = sprintf($this->_('The status warning for the \'%s\' survey was removed.'), $survey->getName());
                    }
                }

                if ($survey->getName() != $surveyor_title) {
                    $values['gsu_survey_name'] = $surveyor_title;
                    $messages[] = sprintf($this->_('The name of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_title);
                }

                if ($survey->getDescription() != $surveyor_description) {
                    $values['gsu_survey_description'] = $surveyor_description;
                    $messages[] = sprintf($this->_('The description of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_description);
                }

                if ($survey->getAvailableLanguages() != $surveyor_languages) {
                    $values['gsu_survey_languages'] = $surveyor_languages;
                    $messages[] = sprintf($this->_('The available languages of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_languages);
                }

                if ($surveyor_warnings) {
                    if ($survey->getSurveyWarnings() != $surveyor_warnings) {
                        $values['gsu_survey_warnings'] = $surveyor_warnings;
                        $messages[] = sprintf($this->_('The warning messages of the \'%s\' survey have been changed to \'%s\'.'), $survey->getName(), $surveyor_warnings);
                    }
                }  elseif (!is_null($survey->getSurveyWarnings())) {
                    $values['gsu_survey_warnings'] = NULL;
                    $messages[] = sprintf($this->_('The warning messages of the \'%s\' survey have been cleared.'), $survey->getName());
                }

            } else { // New record
                if (is_null($lsSurvey['expires'])) {
                    $values['gsu_survey_name']        = $surveyor_title;
                    $values['gsu_survey_description'] = $surveyor_description;
                    $values['gsu_survey_languages']   = $surveyor_languages;
                    $values['gsu_survey_warnings']    = $surveyor_warnings ? $surveyor_warnings : 'OK';
                    $values['gsu_surveyor_active']    = $surveyor_active ? 1 : 0;
                    $values['gsu_active']             = 0;
                    $values['gsu_status']             = $surveyor_status ? $surveyor_status : 'OK';
                    $values['gsu_surveyor_id']        = $sourceSurveyId;
                    $values['gsu_id_source']          = $this->getId();

                    $messages[] = sprintf($this->_('Imported the \'%s\' survey.'), $surveyor_title);
                } else {
                    $messages[] = sprintf($this->_('Skipped the \'%s\' survey because it has an expiry date.'), $surveyor_title);
                }
            }
            $survey->saveSurvey($values, $userId);

            // Check return url description
            $this->_checkReturnURI($sourceSurveyId, $survey, $messages);
        }

        return $messages;
    }

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param \Gems\Tracker\Token $token
     * @param string $language
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems\Tracker\Source\SurveyNotFoundException
     */
    public function copyTokenToSource(\Gems\Tracker\Token $token, $language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $language   = $this->_getLanguage($sourceSurveyId, $language);
        $lsAdapter  = $this->getSourceDatabase();
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsSurvLang = $this->_getSurveyLanguagesTableName();
        $lsSurveys  = $this->_getSurveysTableName();
        $lsTokens   = $this->_getTokenTableName($sourceSurveyId);
        $tokenId    = $this->_getToken($token->getTokenId());

        /********************************
         * Check survey existence / url *
         ********************************/

        // Lookup url information in surveyor, checks for survey being active as well.
        $sql = "SELECT surveyls_url
            FROM $lsSurveys INNER JOIN $lsSurvLang
                ON sid = surveyls_survey_id
             WHERE sid = ?
                AND surveyls_language = ?
                AND active='Y'
             LIMIT 1";
        $currentUrl = $lsResultFetcher->fetchOne($sql, [$sourceSurveyId, $language]);

        // No field was returned
        if (null === $currentUrl) {
            throw new \Gems\Tracker\Source\SurveyNotFoundException(sprintf('The survey with id %d for token %s does not exist.', $surveyId, $tokenId), sprintf('The Lime Survey id is %s', $sourceSurveyId));
        }

        /*****************************
         * Set the end_of_survey uri *
         *****************************/

        if (!\MUtil\Console::isConsole()) {
            // For optimal use, this assumes that most organizations using a survey use the same url.
            // If not, then this url might change regularly, but things will remain working as the
            // final url shown is dependent by the token level return url stored in the token table.
            $newUrl = $this->_getReturnURI($token->getOrganization());

            // Make sure the url is set correctly in surveyor.
            if ($currentUrl != $newUrl) {

                //$where = $lsDb->quoteInto('surveyls_survey_id = ? AND ', $sourceSurveyId) .
                //    $lsDb->quoteInto('surveyls_language = ?', $language);

                $sql = new Sql($lsAdapter);
                $update = $sql->update($lsSurvLang)
                    ->set([
                        'surveyls_url' => $newUrl,
                    ])->where([
                        'surveyls_survey_id' => $sourceSurveyId,
                        'surveyls_language' =>  $language,
                    ]);
                $sql->prepareStatementForSqlObject($update)->execute();

                if (\Gems\Tracker::$verbose) {
                    \MUtil\EchoOut\EchoOut::r("From $currentUrl\n to $newUrl", "Changed return url for $language version of $surveyId.");
                }
            }
        }

        /****************************************
         * Insert token in table (if not there) *
         ****************************************/
        $validDates = $this->getValidDates($token);

        // Get the mapped values
        $values = $this->_fillAttributeMap($token) + $validDates;
        // Apparently it is possible to have this value filled without a survey questionnaire.
        $values['completed'] = 'N';
        if ($token->isCompleted()) {
            $completionTime = $token->getCompletionTime();
            if ($completionTime instanceof \DateTimeInterface) {
                $values['completed'] = $completionTime->format(self::LS_DB_COMPLETION_FORMAT);
            }
        }

        $rows = 0;
        if ($oldValues = $lsResultFetcher->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", [$tokenId])) {
            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                /*if (\Gems\Tracker::$verbose) {
                    $echo = '';
                    foreach ($values as $key => $val) {
                        $echo .= $key . ': ' . $oldValues[$key] . ' => ' . $val . "\n";
                    }
                    \MUtil\EchoOut\EchoOut::r($echo, "Updated limesurvey values for $tokenId");
                }*/

                $sql = new Sql($lsAdapter);
                $update = $sql->update($lsTokens)->set($values)->where(['token' => $tokenId]);
                $rows = $sql->prepareStatementForSqlObject($update)->execute()->getAffectedRows();
            }
        } else {
            /*if (\Gems\Tracker::$verbose) {
                \MUtil\EchoOut\EchoOut::r($values, "Inserted $tokenId into limesurvey");
            }*/
            $values['token'] = $tokenId;

            $sql = new Sql($lsAdapter);
            $insert = $sql->insert($lsTokens)->values($values);
            $rows = $sql->prepareStatementForSqlObject($insert)->execute()->getAffectedRows();
        }

        if ($rows) {
            //If we have changed something, invalidate the cache
            $token->cacheReset();
        }

        return $rows ? 1 : 0;
    }

    /**
     * Fix the tokenattribute descriptions
     *
     * When new token attributes are added, make sure the attributedescriptions field
     * in the surveys table is updated to prevent problems when using these fields
     * in LimeSurvey. For example by referencing them on screen.
     *
     * @param int $sourceSurveyId
     * @return boolean
     */
    protected function fixTokenAttributeDescriptions($sourceSurveyId)
    {
        $lsAdapter = $this->getSourceDatabase();

        $fieldData = array();
        foreach($this->_attributeMap as $fieldName)
        {
            // Only add the attribute fields
            if (substr($fieldName, 0, 10) == 'attribute_') {
                $fieldData[$fieldName] = array(
                    'description' => $fieldName,
                    'mandatory'   => 'N',
                    'show_register'=> 'N',
                    'cpdbmap' => ''
                );
            }
        }

        // We always have fields, so no need to check for empty. Just json_encode the data
        $fields = array(
            $this->_attributeDescriptionsField => json_encode($fieldData)
                );

        $sql = new Sql($lsAdapter);
        $update = $sql->update($this->_getSurveysTableName())->set($fields)->where(['sid' => $sourceSurveyId]);
        $rows = $sql->prepareStatementForSqlObject($update)->execute()->getAffectedRows();

        return (boolean) $rows;
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems\Tracker\Token  $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime($fieldName, \Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        $answers = $token->getRawAnswers();

        if (isset($answers[$fieldName]) && $answers[$fieldName]) {
            return Model::getDateTimeInterface($answers[$fieldName], [self::LS_DB_DATETIME_FORMAT, self::LS_DB_DATE_FORMAT]);
        }

        return null;
    }

    public function getAttributes()
    {
        return array_keys($this->_attributeMap);
    }

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems\Tracker\Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getCompletionTime(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        if ($token->cacheHas('submitdate')) {
            // Use cached value when it exists
            $submitDate = $token->cacheGet('submitdate');

        } else {
            if ($token->hasAnswersLoaded()) {
                // Use loaded answers when loaded
                $submitDate = $this->getAnswerDateTime('submitdate', $token, $surveyId, $sourceSurveyId);

            } else {
                if ($token->cacheHas(self::CACHE_TOKEN_INFO)) {
                    // Use token info when loaded to prevent extra query if not needed
                    $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);

                    $query = isset($tokenInfo['completed']) && ($tokenInfo['completed'] != 'N');
                } else {
                    $query = true;
                }

                if ($query) {
                    if (null === $sourceSurveyId) {
                        $sourceSurveyId = $this->_getSid($surveyId);
                    }

                    $lsResultFetcher = $this->getSourceResultFetcher();
                    $lsSurvey   = $this->_getSurveyTableName($sourceSurveyId);
                    $tokenId    = $this->_getToken($token->getTokenId());

                    $submitDate = $lsResultFetcher->fetchOne("SELECT submitdate FROM $lsSurvey WHERE token = ? LIMIT 1", [$tokenId]);

                    if ($submitDate) {
                        $submitDate = Model::getDateTimeInterface($submitDate, self::LS_DB_DATETIME_FORMAT);
                        if (null === $submitDate) {
                            $submitDate = false; // Null does not trigger cacheHas()
                        }
                    }
                } else {
                    $submitDate = false; // Null does not trigger cacheHas()
                }
            }
            $token->cacheSet('submitdate', $submitDate);
        }

        return $submitDate instanceof DateTimeInterface ? $submitDate : null;
    }

    /**
     * Bulk check token completion
     *
     * Returns all tokens from the input array that are completed, by doing
     * this in bulk we saved overhead and only do a deep check on the completed
     * tokens.
     *
     * @param array $tokenIds
     * @param int $sourceSurveyId
     * @return array
     */
    public function getCompletedTokens($tokenIds, $sourceSurveyId)
    {
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsToken   = $this->_getTokenTableName($sourceSurveyId);

        // Make sure we use tokens in the format LimeSurvey likes
        $tokens = array_map(array($this, '_getToken'), $tokenIds);

        $select = new Select;
        $select->from([$lsToken, 'token'])
                ->where([
                    'token' => $tokens,
                    'completed' => 'N',
                ]);

        $completedTokens = $lsResultFetcher->fetchCol($select);

        // Now make sure we return tokens GemsTracker likes
        if ($completedTokens) {
            $translatedTokens = array();

            // Can not use the map function here since we need a second parameter
            foreach($completedTokens as $token) {
                $translatedTokens[] = $this->_getToken($token, true);
            }

            return $translatedTokens;
        }

        return $completedTokens;
    }

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getDatesList($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        // Not a question but it is a valid date choice
        // $results['submitdate'] = $this->_('Submitdate');

        $results = $this->_getFieldMap($sourceSurveyId, $language)->getQuestionList('D');

        // Prevent html output in date lists
        foreach($results as $key => &$value)
        {
            $value = \MUtil\Html::raw($value);
        }

        return $results;
    }

    /**
     * Returns an array of arrays with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Nested array
     */
    public function getQuestionInformation($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        return $this->_getFieldMap($sourceSurveyId, $language)->getQuestionInformation();
    }

    /**
     * Returns an array containing fieldname => label for each answerable question in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getQuestionList($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        return $this->_getFieldMap($sourceSurveyId, $language)->getQuestionList();
    }

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId \Gems Token Id
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsResultFetcher   = $this->getSourceResultFetcher();
        $lsTab  = $this->_getSurveyTableName($sourceSurveyId);
        $token  = $this->_getToken($tokenId);

        try {
            // Order by ID desc to get the same answers used as in the row retrieved by
            // getRawTokenAnswerRows() in case of double rows
            $values = $lsResultFetcher->fetchRow("SELECT * FROM $lsTab WHERE token = ? ORDER BY id DESC", [$token]);
        } catch (\RuntimeException $exception) {
            $this->logger->error(LogHelper::getMessageFromException($exception, $this->request));
            $values = false;
        }

        if ($values) {
            return $this->_getFieldMap($sourceSurveyId)->mapKeysToTitles($values);
        } else {
            return array();
        }
    }

    /**
     * Returns the answers of multiple tokens in simple raw nested array format,
     * without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param array $filter XXXXX
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        //Now process the filters
        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $tokenField    = $lsSurveyTable . '.token';
        if (is_array($filter)) {
            //first preprocess the tokens
            if (isset($filter['token'])) {
                foreach ((array) $filter['token'] as $key => $tokenId) {
                    $token = $this->_getToken($tokenId);

                    $filter[$tokenField][$key] = $token;
                }
                unset($filter['token']);
            }
        }

        $lsResultFetcher = $this->getSourceResultFetcher();
        // Prevent failure when survey no longer active
        try {
            $rows = $lsResultFetcher->fetchAll($select);
        } catch (\Exception $exc) {
            $rows = false;
        }

        $results = array();
        //@@TODO: check if we really need this, or can just change the 'token' field to have the 'original'
        //        this way other sources that don't perform changes on the token field don't have to loop
        //        over this field. The survey(answer)model could possibly perform the translation for this source
        if ($rows) {
            $map = $this->_getFieldMap($sourceSurveyId);
            if (isset($filter[$tokenField])) {
                foreach ($rows as $values) {
                    $token = $this->_getToken($values['token'], true);  // Reverse map
                    $results[$token] = $map->mapKeysToTitles($values);
                }
                return $results;
            } else {
                //@@TODO If we do the mapping in the select statement, maybe we can gain some performance here
                foreach ($rows as $values) {
                    $results[] = $map->mapKeysToTitles($values);
                }
                return $results;
            }
        }

        return array();
    }

    /**
     * Returns the recordcount for a given filter
     *
     * @param array $filter filter array
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function getRawTokenAnswerRowsCount(array $filter, $surveyId, $sourceSurveyId = null): int
    {
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        $select->columns([
            'count' => new \Laminas\Db\Sql\Expression('COUNT(*)'),
        ]);
        $lsResultFetcher = $this->getSourceResultFetcher();
        $count = $lsResultFetcher->fetchOne($select);

        return intval($count);
    }

    /**
     * Get the select object to use for RawTokenAnswerRows
     *
     * @param array $filter
     * @param int $surveyId
     * @param int $sourceSurveyId
     */
    public function getRawTokenAnswerRowsSelect(array $filter, $surveyId, $sourceSurveyId = null): Select
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsAdapter = $this->getSourceDatabase();
        $lsPlatform = $lsAdapter->getPlatform();
        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenTable  = $this->_getTokenTableName($sourceSurveyId);
        $tokenField    = $lsSurveyTable . '.token';

        $quotedTokenTable  = $lsPlatform->quoteIdentifier($lsTokenTable . '.token');
        $quotedSurveyTable = $lsPlatform->quoteIdentifier($lsSurveyTable . '.token');

        $select = new Select;
        $select->from($lsTokenTable)->columns(array_values($this->_attributeMap))
               ->join($lsSurveyTable, $quotedTokenTable . ' = ' . $quotedSurveyTable);

        //Now process the filters
        if (is_array($filter)) {
            //first preprocess the tokens
            if (isset($filter['token'])) {
                foreach ((array) $filter['token'] as $key => $tokenId) {
                    $token = $this->_getToken($tokenId);

                    $filter[$tokenField][$key] = $token;
                }
                unset($filter['token']);
            }

            // now map the attributes to the right fields
            foreach ($this->_attributeMap as $name => $field) {
                if (isset($filter[$name])) {
                    $filter[$field] = $filter[$name];
                    unset($filter[$name]);
                }
            }
        }

        // Add limit / offset to select and remove from filter
        $this->filterLimitOffset($filter, $select);

        foreach ($filter as $field => $values) {
            $field = $lsPlatform->quoteIdentifier($field);
            if (is_array($values)) {
                $select->where([$field => array_values($values)]);
            } else {
                $select->where([$field => $values]);
            }
        }

        if (\Gems\Tracker::$verbose) {
            \MUtil\EchoOut\EchoOut::r($select->getSqlString(), 'Select');
        }

        return $select;
    }

    /**
     * Gets the time the survey was started according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems\Tracker\Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getStartTime(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        // Always return null!
        // The 'startdate' field is the time of the first save, not the time the user started
        // so Lime Survey does not contain this value.
        return null;
    }

    /**
     * Returns a model for the survey answers
     *
     * @param \Gems\Tracker\Survey $survey
     * @param string $language Optional (ISO) language string
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil\Model\ModelAbstract
     */
    public function getSurveyAnswerModel(\Gems\Tracker\Survey $survey, $language = null, $sourceSurveyId = null)
    {
        static $cache = array();        // working with 'real' cache produces out of memory error

        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($survey->getSurveyId());
        }
        $language = $this->_getLanguage($sourceSurveyId, $language);
        $cacheId  = $sourceSurveyId . strtr($language, '-.', '__');

        if (!array_key_exists($cacheId, $cache)) {
            $model           = $this->tracker->getSurveyModel($survey, $this);
            $fieldMap        = $this->_getFieldMap($sourceSurveyId, $language)->applyToModel($model);
            $cache[$cacheId] = $model;
        }

        return $cache[$cacheId];
    }

    /**
     * Retrieve all fields stored in the token table, and store them in the tokencache
     *
     * @param \Gems\Tracker\Token $token
     * @param int $surveyId
     * @param int $sourceSurveyId
     * @param array $fields
     * @return array
     */
    public function getTokenInfo(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId, array $fields = null)
    {
        if (! $token->cacheHas(self::CACHE_TOKEN_INFO)) {
            if (null === $sourceSurveyId) {
                $sourceSurveyId = $this->_getSid($surveyId);
            }

            $lsTokens = $this->_getTokenTableName($sourceSurveyId);
            $tokenId  = $this->_getToken($token->getTokenId());

            $sql = 'SELECT *
                FROM ' . $lsTokens . '
                WHERE token = ? LIMIT 1';

            try {
                $result = $this->getSourceResultFetcher()->fetchRow($sql, [$tokenId]);
            } catch (\RuntimeException $exception) {
                $this->logger->error(LogHelper::getMessageFromException($exception, $this->request));
                $result = false;
            }

            $token->cacheSet(self::CACHE_TOKEN_INFO, $result);
        } else {
            $result = $token->cacheGet(self::CACHE_TOKEN_INFO);
        }

        if ($fields !== null) $result = array_intersect_key((array) $result, array_flip($fields));

        return $result;
    }

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param string $language
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems\Tracker\Token $token, $language, $surveyId, $sourceSurveyId)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }
        $tokenId = $this->_getToken($token->getTokenId());

        if ($this->_isLanguage($sourceSurveyId, $language)) {
            $langUrl = '/lang/' . $language;
        } else {
            $langUrl = '';
        }

        // <base>/index.php/survey/index/sid/834486/token/234/lang/en
        $baseurl = $this->getBaseUrl();
        $start = $this->config['survey']['limesurvey']['tokenUrlStart'] ?? 'index.php';
        return $baseurl . ('/' == substr($baseurl, -1) ? '' : '/') . $start . 'survey/index/sid/' . $sourceSurveyId . '/token/' . $tokenId . $langUrl . '/newtest/Y';
    }

    /**
     * Get valid from/to dates to send to LimeSurvey depending on the dates of the token
     *
     * @param \Gems\Tracker\Token $token
     * @return array
     */
    public function getValidDates(\Gems\Tracker\Token $token)
    {
        $now = new DateTimeImmutable();
        // For extra protection, we add valid from/to dates as needed instead of leaving them in GemsTracker only
        $tokenFrom  = $token->getValidFrom();
        $tokenUntil = $token->getValidUntil();

        // Always set all dated, so LimeSurvey will check the token
        if ($tokenFrom) {
            $lsFrom = $tokenFrom;
            if ($tokenUntil) {
                $lsUntil = $tokenUntil;
            } else {
                // To end of day. If entering via GemsTracker it will always be updated as long as the token is still valid
                $lsUntil = $now->setTime(23,59,59);
            }
        } else {
            // No start date, use save date in the past to block access
            $lsFrom  = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1900-01-01 00:00:00');
            $lsUntil = $lsFrom;
        }

        $values = [
            'validfrom'  => $lsFrom->format(self::LS_DB_DATETIME_FORMAT),
            'validuntil' => $lsUntil->format(self::LS_DB_DATETIME_FORMAT)
        ];

        return $values;
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);
        return (boolean) $tokenInfo;
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int $surveyId \Gems Survey Id
     * @param $answers array Field => Value array, can be empty
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null)
    {
        $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);
        if (isset($tokenInfo['completed']) && $tokenInfo['completed'] != 'N') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the answers passed on.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param array $answers Field => Value array
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return bool true When answers changed
     */
    public function setRawTokenAnswers(\Gems\Tracker\Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsAdapter = $this->getSourceDatabase();
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsTab     = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenId = $this->_getToken($token->getTokenId());

        // \MUtil\EchoOut\EchoOut::track($answers);

        $answers = $this->_getFieldMap($sourceSurveyId)->mapTitlesToKeys($answers);
        $answers = $this->_filterAnswersOnly($sourceSurveyId, $answers);

        // \MUtil\EchoOut\EchoOut::track($answers);

        $sql = new Sql($lsAdapter);
        if ($lsResultFetcher->fetchOne("SELECT token FROM $lsTab WHERE token = ?", [$lsTokenId])) {
            if ($answers) {
                $update = $sql->update($lsTab)->set($answers)->where(['token' => $lsTokenId]);
                $rows = $sql->prepareStatementForSqlObject($update)->execute()->getAffectedRows();
                return ($rows > 0);
            }
        } else {
            $current = new \MUtil\Db\Expr\CurrentTimestamp();

            $answers['token']         = $lsTokenId;
            $answers['startlanguage'] = $this->locale->getLanguage();
            $answers['datestamp']     = $current;
            $answers['startdate']     = $current;

            $insert = $sql->insert($lsTab)->values($answers);
            $sql->prepareStatementForSqlObject($insert)->execute();

            return true;
        }
        return false;
    }

    /**
     * Sets the completion time.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param \DateTimeInterface|null $completionTime \DateTimeInterface or null
     * @param int $surveyId \Gems Survey Id (actually required)
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setTokenCompletionTime(\Gems\Tracker\Token $token, $completionTime, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsAdapter = $this->getSourceDatabase();
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsTabSurv = $this->_getSurveyTableName($sourceSurveyId);
        $lsTabTok  = $this->_getTokenTableName($sourceSurveyId);
        $lsTokenId = $this->_getToken($token->getTokenId());
        $where     = ['token' => $lsTokenId];
        $current   = new \MUtil\Db\Expr\CurrentTimestamp();

        if ($completionTime instanceof \DateTimeInterface) {
            $answers['submitdate']  = $completionTime->format(self::LS_DB_DATETIME_FORMAT);
            $tokenData['completed'] = $completionTime->format(self::LS_DB_COMPLETION_FORMAT);
        } else {
            $answers['submitdate']  = null;
            $tokenData['completed'] = 'N';
        }

        // Set for the survey
        $sql = new Sql($lsAdapter);
        if ($lsResultFetcher->fetchOne("SELECT token FROM $lsTabSurv WHERE token = ?", [$lsTokenId])) {
            $update = $sql->update($lsTabSurv)->set($answers)->where($where);
            $sql->prepareStatementForSqlObject($update)->execute();

        } elseif ($completionTime instanceof \DateTimeInterface) {
            $answers['token']         = $lsTokenId;
            $answers['startlanguage'] = $this->locale->getLanguage();
            $answers['datestamp']     = $current;
            $answers['startdate']     = $current;

            $insert = $sql->insert($lsTabSurv)->values($answers);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }

        // Set for the token
        if ($lsResultFetcher->fetchOne("SELECT token FROM $lsTabTok WHERE token = ?", [$lsTokenId])) {
            $update = $sql->update($lsTabTok)->set($tokenData)->where($where);
            $sql->prepareStatementForSqlObject($update)->execute();

        } elseif ($completionTime instanceof \DateTimeImmutable) {

            $tokenData['token'] = $lsTokenId;
            $tokenData = $tokenData + $this->_fillAttributeMap($token);

            $insert = $sql->insert($lsTabTok)->values($tokenData);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }
        $token->cacheReset();
    }

    /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param \Gems\Tracker\Token $token
     * @param int $surveyId \Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(\Gems\Tracker\Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsAdapter = $this->getSourceDatabase();
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsTokens = $this->_getTokenTableName($sourceSurveyId);
        $tokenId  = $this->_getToken($token->getTokenId());

        if (null === $consentCode) {
            $consentCode = (string) $token->getConsentCode();
        }
        $values[$this->_attributeMap['consentcode']] = $consentCode;

        if ($oldValues = $lsResultFetcher->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", [$tokenId])) {

            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                if (\Gems\Tracker::$verbose) {
                    $echo = '';
                    foreach ($values as $key => $val) {
                        $echo .= $key . ': ' . $oldValues[$key] . ' => ' . $val . "\n";
                    }
                    \MUtil\EchoOut\EchoOut::r($echo, "Updated limesurvey values for $tokenId");
                }

                $sql = new Sql($lsAdapter);
                $update = $sql->update($lsTokens)->set($values)->where(['token' => $tokenId]);
                $result = $sql->prepareStatementForSqlObject($update)->execute();

                $rows = $result->getAffectedRows();
                if ($rows) {
                    //If we have changed something, invalidate the cache
                    $token->cacheReset('tokenInfo');
                }
                return $rows;
            }
        }

        return 0;
    }

    /**
     * Get the table structure of a survey table
     *
     * @param $sourceSurveyId int Limesurvey survey ID
     * @return array List of table structure
     */
    public function getSurveyTableStructure($sourceSurveyId)
    {
        $tableStructure = $this->_getFieldMap($sourceSurveyId)->getSurveyTableStructure();

        return $tableStructure;
    }

    /**
     * Get the table structure of a survey token table
     *
     * @param $sourceSurveyId int Limesurvey survey ID
     * @return array List of table structure of survey token table
     */
    public function getTokenTableStructure($sourceSurveyId)
    {
        $tableStructure = $this->_getFieldMap($sourceSurveyId)->getTokenTableStructure();

        return $tableStructure;
    }

    /**
     * Execute a Database query on the limesurvey Database
     *
     * @param $sourceSurveyId int Limesurvey survey ID
     * @param $sql mixed SQL query to perform on the limesurvey database
     * @param array $bindValues optional bind values for the Query
     */
    public function lsDbQuery($sourceSurveyId, $sql, $bindValues=array())
    {
        $this->_getFieldMap($sourceSurveyId)->lsDbQuery($sql, $bindValues);
    }
}
