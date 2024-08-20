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
use Gems\Cache\HelperAdapter;
use Gems\ConfigProvider;
use Gems\Db\ResultFetcher;
use Gems\Encryption\ValueEncryptor;
use Gems\Html;
use Gems\Locale\Locale;
use Gems\Log\Loggers;
use Gems\Log\LogHelper;
use Gems\Repository\CurrentUrlRepository;
use Gems\Tracker;
use Gems\Tracker\Survey;
use Gems\Tracker\Token;
use Gems\Tracker\Token\TokenLibrary;
use Gems\User\Organization;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Metadata\Source\Factory;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Ddl\AlterTable;
use Laminas\Db\Sql\Ddl\Column\Varchar;
use Psr\Log\LoggerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\FullDataInterface;

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
    public const CACHE_TOKEN_INFO = 'tokenInfo';

    public const LS_DB_COMPLETION_FORMAT = 'Y-m-d H:i';
    public const LS_DB_DATE_FORMAT       = 'Y-m-d';
    public const LS_DB_DATETIME_FORMAT   = 'Y-m-d H:i:s';

    public const QUESTIONS_TABLE    = 'questions';
    public const SURVEY_TABLE       = 'survey_';
    public const SURVEYS_LANG_TABLE = 'surveys_languagesettings';
    public const SURVEYS_TABLE      = 'surveys';
    public const TOKEN_TABLE        = 'tokens_';

    /**
     * @var string[] metadata fields that are included in a survey table
     */
    public static array $metaFields = [
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
    protected string $_anonymizedField = 'anonymized';

    /**
     *
     * @var string The field that holds the token attribute descriptions in the surveys table
     */
    protected string $_attributeDescriptionsField = 'attributedescriptions';

    /**
     * A map containing attributename => databasefieldname mappings
     *
     * Should contain maps for respondentid, organizationid and consentcode.
     * @var array<string, string>
     */
    protected array $_attributeMap = [
        'respondentid'   => 'attribute_1',
        'organizationid' => 'attribute_2',
        'consentcode'    => 'attribute_3',
        'resptrackid'    => 'attribute_4',
    ];

    /**
     * @var string Default language when none is specified nor found
     */
    protected string $defaultLanguage = 'en';

    /**
     *
     * @var array of \Gems\Tracker\Source\LimeSurvey3m00FieldMap
     */
    private array $_fieldMaps = [];

    /**
     *
     * @var array of string
     */
    private array $_languageMap = [];

    /**
     * The default text length attribute fields should have.
     *
     * @var int
     */
    protected int $attributeSize = 255;

    /**
     *
     * @var string class name for creating field maps
     */
    protected string $fieldMapClass = LimeSurvey3m00FieldMap::class;

    protected readonly LoggerInterface $logger;

    protected string|null $siteName = null;

    protected array $surveyConfig;

    public function __construct(
        array $_sourceData,
        ResultFetcher $_gemsResultFetcher,
        TranslatorInterface $translate,
        TokenLibrary $tokenLibrary,
        Tracker $tracker,
        ValueEncryptor $valueEncryptor,
        array $config,
        Loggers $loggers,
        protected readonly HelperAdapter $cache,
        protected readonly Locale $locale,

        protected readonly CurrentUrlRepository $currentUrlRepository,
    ) {
        parent::__construct($_sourceData, $_gemsResultFetcher, $translate, $tokenLibrary, $tracker, $valueEncryptor, $config);

        if (isset($config['locale']['default'])) {
            $this->defaultLanguage = $config['locale']['default'];
        }
        $this->logger = $loggers->getLogger(ConfigProvider::ERROR_LOGGER);
        $this->siteName = $config['app']['name'] ?? null;

        $this->surveyConfig = $config['survey'] ?? [];
    }

    /**
     * Checks the return URI in LimeSurvey and sets it to the correct one when needed
     *
     * @see checkSurvey()
     *
     * @param int|string $sourceSurveyId
     * @param Survey $survey
     * @param array $messages
     */
    protected function _checkReturnURI(int|string $sourceSurveyId, Survey $survey, array &$messages): void
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
            $messages[] = sprintf($this->translate->_('The description of the exit url description was changed for %s languages in survey \'%s\'.'), $langChanges, $survey->getName());
        }
    }

    /**
     * Check a token table for any changes needed by this version.
     *
     * @param array $tokenTable
     * @return array<string, string> Fieldname => change field commands
     */
    protected function _checkTokenTable(array $tokenTable): array
    {
        $missingFields = array();

        $tokenLength = $this->_extractFieldLength($tokenTable['token']['Type']);
        if ($tokenLength < $this->tokenLibrary->getLength()) {
            $tokenLength = $this->tokenLibrary->getLength();
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
    private function _extractFieldLength(string $typeDescr): int
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
     * @param Token $token
     * @return array{string: string|int}  Fieldname => value type
     */
    protected function _fillAttributeMap(Token $token): array
    {
        $values[$this->_attributeMap['respondentid']]   =
                substr(strval($token->getRespondentId()), 0, $this->attributeSize);
        $values[$this->_attributeMap['organizationid']] =
                substr(strval($token->getOrganizationId()), 0, $this->attributeSize);
        $values[$this->_attributeMap['consentcode']]    =
                substr($token->getConsentCode(), 0, $this->attributeSize);
        $values[$this->_attributeMap['resptrackid']]    =
                substr(strval($token->getRespondentTrackId()), 0, $this->attributeSize);

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
     * @param int|string $sourceSurveyId Survey ID
     * @param array $answers
     * @return array
     */
    protected function _filterAnswersOnly(int|string $sourceSurveyId, array $answers): array
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
     * @param int|string $sourceSurveyId Survey ID
     * @param ?string $language      Optional (ISO) Language, uses default language for survey when null
     * @return LimeSurvey3m00FieldMap
     */
    protected function _getFieldMap(int|string $sourceSurveyId, ?string $language = null): LimeSurvey3m00FieldMap
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
     * @param int|string $sourceSurveyId Survey ID
     * @param string|null $language       (ISO) Language
     * @return string (ISO) Language
     */
    protected function _getLanguage(int|string $sourceSurveyId, string|null $language): string
    {
        if (! isset($this->_languageMap[$sourceSurveyId][$language])) {
            if ($language && $this->_isLanguage($sourceSurveyId, $language)) {
                $this->_languageMap[$sourceSurveyId][$language] = $language;
            } else {
                $lsResultFetcher = $this->getSourceResultFetcher();

                $sql = 'SELECT language
                    FROM ' . $this->_getSurveysTableName() . '
                    WHERE sid = ?';

                $this->_languageMap[$sourceSurveyId][$language] = $lsResultFetcher->fetchOne($sql, [$sourceSurveyId]);

                if (! $this->_languageMap[$sourceSurveyId][$language]) {
                    $this->_languageMap[$sourceSurveyId][$language] = $language ?? $this->defaultLanguage;
                }
            }
        }

        return $this->_languageMap[$sourceSurveyId][$language];
    }

    /**
     * Get the return URI to return from LimeSurvey to GemsTracker
     *
     * @param Organization|null $organization
     * @return string
     */
    protected function _getReturnURI(Organization|null $organization = null): string
    {
        if ($organization) {
            $currentUrl = $organization->getPreferredSiteUrl();
        } else {
            $currentUrl = $this->currentUrlRepository->getCurrentUrl();
        }
        return $currentUrl . '/ask/return/{TOKEN}';
    }

    /**
     * Get the return URI description to set in LimeSurvey
     *
     * @param string $language
     * @return string
     */
    protected function _getReturnURIDescription(string $language): string
    {
        $message = $this->translate->_('Back', [], null, $language);
        if ($this->siteName) {
            $message = sprintf($this->translate->_('Back to %s', [], null, $language), $this->siteName);
        }

        return $message;
    }

    /**
     * Looks up the LimeSurvey Survey ID
     *
     * @param int $surveyId
     * @return int
     */
    protected function _getSid(int $surveyId): int
    {
        return $this->tracker->getSurvey($surveyId)->getSourceSurveyId();
    }

    /**
     * Returns all surveys for synchronization
     *
     * @return array{int: int} of sourceId values or false
     */
    protected function _getSourceSurveysForSynchronisation(): array
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
    protected function _getSurveyLanguagesTableName(): string
    {
        return $this->addDatabasePrefix(self::SURVEYS_LANG_TABLE, false);
    }

    /**
     * There exists a survey table for each active survey. The table contains the answers to the survey
     *
     * @param int|string $sourceSurveyId Survey ID
     * @return string Name of survey table for this survey
     */
    protected function _getSurveyTableName(int|string $sourceSurveyId): string
    {
        return $this->addDatabasePrefix(self::SURVEY_TABLE . $sourceSurveyId, false);
    }

    /**
     * The survey table contains one row per each survey in LS
     *
     * @return string Name of survey table
     */
    protected function _getSurveysTableName(): string
    {
        return $this->addDatabasePrefix(self::SURVEYS_TABLE, false);
    }

    /**
     * Replaces hyphen with underscore so LimeSurvey won't choke on it
     *
     * @param string $tokenId
     * @param bool $reverse  Reverse the action to go from limesurvey to GemsTracker token (default is false)
     * @return string
     */
    protected function _getToken(string $tokenId, bool $reverse = false): string
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
     * @param int|string $sourceSurveyId Survey ID
     * @return string Name of token table for this survey
     */
    protected function _getTokenTableName(int|string $sourceSurveyId): string
    {
        return $this->addDatabasePrefix(self::TOKEN_TABLE . $sourceSurveyId, false);
    }

    /**
     * Check if the specified language is available in Lime Survey
     *
     * @param int|string $sourceSurveyId Survey ID
     * @param string $language       (ISO) Language
     * @return bool True when the language is an existing language
     */
    protected function _isLanguage(int|string $sourceSurveyId, string $language): bool
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
     * @param int $userId    ID of the user who takes the action (for logging)
     * @return bool  True if the source is active
     */
    public function checkSourceActive(int $userId): bool
    {
        // The only method to check if it is active is by getting all the tables,
        // since the surveys table may be empty so we just check for existence.
        $sourceDb  = $this->getSourceDatabase();
        $metadata = Factory::createSourceFromAdapter($sourceDb);
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
     * @param int|string|null $sourceSurveyId
     * @param int|string|null $surveyId
     * @param int $userId
     * @return array{int: string}  Array of messages
     */
    public function checkSurvey(int|string|null $sourceSurveyId, int|string|null $surveyId, int $userId): array
    {
        $messages = array();
        $survey   = $this->tracker->getSurvey($surveyId);

        if (null === $sourceSurveyId) {
            // Was removed
            $values['gsu_active'] = 0;
            $values['gsu_surveyor_active'] = 0;
            $values['gsu_status'] = 'Survey was removed from source.';

            if ($survey->saveSurvey($values, $userId)) {
                $messages[] = sprintf($this->translate->_('The \'%s\' survey is no longer active. The survey was removed from LimeSurvey!'), $survey->getName());
            }
        } else {
            $lsAdapter = $this->getSourceDatabase();
            $lsResultFetcher = $this->getSourceResultFetcher();

            // SELECT sid, surveyls_title AS short_title, surveyls_description AS description, active, datestamp, ' . $this->_anonymizedField . '
            $select = $lsResultFetcher->getSelect($this->_getSurveysTableName());
            // 'alloweditaftercompletion' ?
            $select->columns(['active', 'datestamp', 'language', 'additional_languages', 'autoredirect', 'alloweditaftercompletion', 'allowregister', 'listpublic', 'tokenanswerspersistence', 'expires', $this->_anonymizedField])
                    ->join($this->_getSurveyLanguagesTableName(),
                           'sid = surveyls_survey_id AND language = surveyls_language',
                           ['surveyls_title', 'surveyls_description'])
                    ->where(['sid' => $sourceSurveyId]);
            $lsSurvey = $lsResultFetcher->fetchRow($select);

            $surveyor_title = mb_substr(Html::removeMarkup(html_entity_decode($lsSurvey['surveyls_title'])), 0, 100);
            $surveyor_description = mb_substr(Html::removeMarkup(html_entity_decode($lsSurvey['surveyls_description'])), 0, 100);
            $surveyor_status = '';
            $surveyor_warnings = '';

            // AVAILABLE LANGUAGES
            $surveyor_languages = mb_substr(Html::removeMarkup(html_entity_decode($lsSurvey['language'])), 0, 100);
            $surveyor_additional_languages = mb_substr(Html::removeMarkup(html_entity_decode($lsSurvey['additional_languages'])), 0, 100);
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
                    $messages[] = sprintf($this->translate->_("Corrected anonymization for survey '%s'"), $surveyor_title);

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
                    // Re-index the tokenTable data by column name instead of by numeric index.
                    $tokenTable = $this->indexByField($tokenTable);
                } catch (\RuntimeException $e) {
                    $tokenTable = false;
                }
                if ($tokenTable) {
                    $missingFields   = $this->_checkTokenTable($tokenTable);

                    if ($missingFields) {
                        $sql    = "ALTER TABLE " . $this->_getTokenTableName($sourceSurveyId) . " " . implode(', ', $missingFields);
                        $fields = implode($this->translate->_(', '), array_keys($missingFields));
                        // \MUtil\EchoOut\EchoOut::track($missingFields, $sql);
                        try {
                            // FIXME: This is not platform agnostic!
                            $lsAdapter->query($sql, $lsAdapter::QUERY_MODE_EXECUTE);
                            $messages[] = sprintf($this->translate->_("Added to token table '%s' the field(s): %s"), $surveyor_title, $fields);
                        } catch (\RuntimeException $e) {
                            $surveyor_status .= 'Token attributes could not be created. ';
                            $surveyor_status .= $e->getMessage() . ' ';

                            $messages[] = sprintf($this->translate->_("Attribute fields not created for token table for '%s'"), $surveyor_title);
                            $messages[] = sprintf($this->translate->_('Required fields: %s'), $fields);
                            $messages[] = $e->getMessage();

                            // Maximum reporting for this case
                            \MUtil\EchoOut\EchoOut::r($missingFields, 'Missing fields for ' . $surveyor_title);
                            \MUtil\EchoOut\EchoOut::r($e);
                        }
                    }

                    if ($this->fixTokenAttributeDescriptions($sourceSurveyId)) {
                        $messages[] = sprintf($this->translate->_("Updated token attribute descriptions for '%s'"), $surveyor_title);
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

                    $messages[] = sprintf($this->translate->_('The status of the \'%s\' survey has changed.'), $survey->getName());
                }

                // Reset to inactive if the surveyor survey has become inactive.
                if ($survey->isActive() && $surveyor_status) {
                    $values['gsu_active'] = 0;
                    $messages[] = sprintf($this->translate->_('Survey \'%s\' IS NO LONGER ACTIVE!!!'), $survey->getName());
                }

                if (mb_substr($surveyor_status,  0,  127) != (string) $survey->getStatus()) {
                    if ($surveyor_status) {
                        $values['gsu_status'] = mb_substr($surveyor_status,  0,  127);
                        $messages[] = sprintf($this->translate->_('The status of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $values['gsu_status']);
                    } elseif ($survey->getStatus() != 'OK') {
                        $values['gsu_status'] = 'OK';
                        $messages[] = sprintf($this->translate->_('The status warning for the \'%s\' survey was removed.'), $survey->getName());
                    }
                }

                if ($survey->getName() != $surveyor_title) {
                    $values['gsu_survey_name'] = $surveyor_title;
                    $messages[] = sprintf($this->translate->_('The name of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_title);
                }

                if ($survey->getDescription() != $surveyor_description) {
                    $values['gsu_survey_description'] = $surveyor_description;
                    $messages[] = sprintf($this->translate->_('The description of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_description);
                }

                if ($survey->getAvailableLanguages() != $surveyor_languages) {
                    $values['gsu_survey_languages'] = $surveyor_languages;
                    $messages[] = sprintf($this->translate->_('The available languages of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_languages);
                }

                if ($surveyor_warnings) {
                    if ($survey->getSurveyWarnings() != $surveyor_warnings) {
                        $values['gsu_survey_warnings'] = $surveyor_warnings;
                        $messages[] = sprintf($this->translate->_('The warning messages of the \'%s\' survey have been changed to \'%s\'.'), $survey->getName(), $surveyor_warnings);
                    }
                }  elseif (!is_null($survey->getSurveyWarnings())) {
                    $values['gsu_survey_warnings'] = NULL;
                    $messages[] = sprintf($this->translate->_('The warning messages of the \'%s\' survey have been cleared.'), $survey->getName());
                }

            } elseif (is_null($lsSurvey['expires'])) {
                $values['gsu_survey_name']        = $surveyor_title;
                $values['gsu_survey_description'] = $surveyor_description;
                $values['gsu_survey_languages']   = $surveyor_languages;
                $values['gsu_survey_warnings']    = $surveyor_warnings ?: 'OK';
                $values['gsu_surveyor_active']    = $surveyor_active ? 1 : 0;
                $values['gsu_active']             = 0;
                $values['gsu_status']             = $surveyor_status ?: 'OK';
                $values['gsu_surveyor_id']        = $sourceSurveyId;
                $values['gsu_id_source']          = $this->getId();

                $messages[] = sprintf($this->translate->_('Imported the \'%s\' survey.'), $surveyor_title);
            } else {
                $messages[] = sprintf($this->translate->_('Skipped the \'%s\' survey because it has an expiry date.'), $surveyor_title);
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
     * @param Token $token
     * @param string $language
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws SurveyNotFoundException
     */
    public function copyTokenToSource(Token $token, string $language, int $surveyId, int|string|null $sourceSurveyId = null): int
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
            throw new SurveyNotFoundException(sprintf('The survey with id %d for token %s does not exist.', $surveyId, $tokenId) . ' ' . sprintf('The Lime Survey id is %s', $sourceSurveyId));
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

                if (Tracker::$verbose) {
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
            if ($completionTime instanceof DateTimeInterface) {
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
     * @param int|string $sourceSurveyId
     * @return bool
     */
    protected function fixTokenAttributeDescriptions(int|string $sourceSurveyId): bool
    {
        $lsAdapter = $this->getSourceDatabase();

        $fieldData = array();
        foreach($this->_attributeMap as $fieldName)
        {
            // Only add the attribute fields
            if (str_starts_with($fieldName, 'attribute_')) {
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

        return (bool) $rows;
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param Token  $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime(string $fieldName, Token $token, int $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface
    {
        $answers = $token->getRawAnswers();

        if (isset($answers[$fieldName]) && $answers[$fieldName]) {
            if ($answers[$fieldName] instanceof DateTimeInterface) {
                return $answers[$fieldName];
            }
            $output = DateTimeImmutable::createFromFormat(self::LS_DB_DATETIME_FORMAT, $answers[$fieldName]);
            if ($output instanceof DateTimeInterface) {
                return $output;
            }
            $output = DateTimeImmutable::createFromFormat(self::LS_DB_DATE_FORMAT, $answers[$fieldName]);
            if ($output instanceof DateTimeInterface) {
                return $output;
            }
        }

        return null;
    }

    /**
     * Returns all the gemstracker names for attributes stored in source for a token
     *
     * @return array<int, string>
     */
    public function getAttributes(): array
    {
        return array_keys($this->_attributeMap);
    }

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case Token will do it's best to keep
     * track by itself.
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getCompletionTime(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface
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
                        $submitDate = DateTimeImmutable::createFromFormat(self::LS_DB_DATETIME_FORMAT, $submitDate);
                        if (! $submitDate) {
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
     * @param int|string $sourceSurveyId
     * @return array
     */
    public function getCompletedTokens(array $tokenIds, int|string $sourceSurveyId): array
    {
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsToken   = $this->_getTokenTableName($sourceSurveyId);

        // Make sure we use tokens in the format LimeSurvey likes
        $tokens = array_map(array($this, '_getToken'), $tokenIds);

        $select = $lsResultFetcher->getSelect($lsToken);
        $select->columns(['token'])
            ->where([
                'token' => $tokens,
                'completed != \'N\'',
            ]);

        $completedTokens = $lsResultFetcher->fetchCol($select);
        $translatedTokens = [];

        // Now make sure we return tokens GemsTracker likes
        if ($completedTokens) {
            // Can not use the map function here since we need a second parameter
            foreach($completedTokens as $token) {
                $translatedTokens[] = $this->_getToken($token, true);
            }
        }

        return $translatedTokens;
    }

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array{string: string}  Fieldname => label
     */
    public function getDatesList(string $language, int $surveyId, int|string|null $sourceSurveyId = null): array
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        // Not a question but it is a valid date choice
        // $results['submitdate'] = $this->translate->_('Submitdate');

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
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array Nested array
     */
    public function getQuestionInformation(string $language, int $surveyId, int|string|null $sourceSurveyId = null): array
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
     * @param int $surveyId \Gems Survey ID
     * @param string|null $language   (ISO) language string
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array fieldname => label
     */
    public function getQuestionList(int $surveyId, string|null $language = null, int|string|null $sourceSurveyId = null): array
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
     * @param string $tokenId \Gems Token ID
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow(string $tokenId, int $surveyId, int|string|null $sourceSurveyId = null): array
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
            $this->logger->error(LogHelper::getMessageFromException($exception));
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
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, int $surveyId, int|string|null $sourceSurveyId = null): array
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        //Now process the filters
        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $tokenField    = $lsSurveyTable . '.token';

        //first preprocess the tokens
        if (isset($filter['token'])) {
            foreach ((array) $filter['token'] as $key => $tokenId) {
                $token = $this->_getToken($tokenId);

                $filter[$tokenField][$key] = $token;
            }
            unset($filter['token']);
        }

        $lsResultFetcher = $this->getSourceResultFetcher();
        // Prevent failure when survey no longer active
        try {
            $rows = $lsResultFetcher->fetchAll($select);
        } catch (\Exception $e) {
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
            } else {
                //@@TODO If we do the mapping in the select statement, maybe we can gain some performance here
                foreach ($rows as $values) {
                    $results[] = $map->mapKeysToTitles($values);
                }
            }
            return $results;
        }

        return array();
    }

    /**
     * Returns the recordcount for a given filter
     *
     * @param array $filter filter array
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     */
    public function getRawTokenAnswerRowsCount(array $filter, int $surveyId, int|string|null $sourceSurveyId = null): int
    {
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        $select->columns([
            'count' => new Expression('COUNT(*)'),
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
     * @param int|string|null $sourceSurveyId
     * @return Select
     */
    public function getRawTokenAnswerRowsSelect(array $filter, int $surveyId, int|string|null $sourceSurveyId = null): Select
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $sourceResultFetcher = $this->getSourceResultFetcher();

        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenTable  = $this->_getTokenTableName($sourceSurveyId);
        $tokenField    = $lsSurveyTable . '.token';

        $quotedTokenTable  = $lsTokenTable . '.token';
        $quotedSurveyTable = $lsSurveyTable . '.token';

        $select = $sourceResultFetcher->getSelect($lsTokenTable);
        $select->columns(array_values($this->_attributeMap))
            ->join($lsSurveyTable, $quotedTokenTable . ' = ' . $quotedSurveyTable);

        //Now process the filters
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

        // Add limit / offset to select and remove from filter
        $this->filterLimitOffset($filter, $select);

        foreach ($filter as $field => $values) {
            if (is_array($values)) {
                $select->where([$field => array_values($values)]);
            } else {
                $select->where([$field => $values]);
            }
        }

        if (Tracker::$verbose) {
            \MUtil\EchoOut\EchoOut::r($select->getSqlString(), 'Select');
        }

        return $select;
    }

    /**
     * Gets the time the survey was started according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case Token will do it's best to keep
     * track by itself.
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getStartTime(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface
    {
        // Always return null!
        // The 'startdate' field is the time of the first save, not the time the user started
        // so Lime Survey does not contain this value.
        return null;
    }

    /**
     * Returns a model for the survey answers
     *
     * @param Survey $survey
     * @param ?string $language Optional (ISO) language string
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return FullDataInterface
     */
    public function getSurveyAnswerModel(Survey $survey, ?string $language = null, int|string|null $sourceSurveyId = null): FullDataInterface
    {
        static $cache = array();        // working with 'real' cache produces out of memory error

        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($survey->getSurveyId());
        }
        $language = $this->_getLanguage($sourceSurveyId, $language);
        $cacheId  = $sourceSurveyId . strtr($language, '-.', '__');

        if (!array_key_exists($cacheId, $cache)) {
            $model           = $this->tracker->getSurveyModel($survey, $this);
            $this->_getFieldMap($sourceSurveyId, $language)->applyToModel($model->getMetaModel());
            $cache[$cacheId] = $model;
        }

        return $cache[$cacheId];
    }

    /**
     * Retrieve all fields stored in the token table, and store them in the tokencache
     *
     * @param Token $token
     * @param int $surveyId
     * @param int|string|null $sourceSurveyId
     * @param array|null $fields
     * @return array|null
     */
    public function getTokenInfo(Token $token, int $surveyId, int|string|null $sourceSurveyId, array|null $fields = null): array|null
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
                $this->logger->error(LogHelper::getMessageFromException($exception));
                $result = null;
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
     * @param Token $token \Gems token object
     * @param string $language
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(Token $token, string $language, int $surveyId, int|string|null $sourceSurveyId = null): string
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
        $start = $this->surveyConfig['limesurvey']['tokenUrlStart'] ?? 'index.php';
        return $baseurl . (str_ends_with($baseurl, '/') ? '' : '/') . $start . 'survey/index/sid/' . $sourceSurveyId . '/token/' . $tokenId . $langUrl . '/newtest/Y';
    }

    /**
     * Get valid from/to dates to send to LimeSurvey depending on the dates of the token
     *
     * @param Token $token
     * @return array<string, string>
     */
    public function getValidDates(Token $token): array
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
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return bool
     */
    public function inSource(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): bool
    {
        $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);
        $result = (bool) $tokenInfo;
        return $result;
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return bool True if the token has completed
     */
    public function isCompleted(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): bool
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
     * @param Token $token \Gems token object
     * @param array $answers Field => Value array
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return bool true When answers changed
     */
    public function setRawTokenAnswers(Token $token, array $answers, int $surveyId, int|string|null $sourceSurveyId = null): bool
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
            $current = new Expression('CURRENT_TIMESTAMP');

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
     * @param Token $token \Gems token object
     * @param DateTimeInterface|null $completionTime DateTimeInterface or null
     * @param int $surveyId \Gems Survey ID (actually required)
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     */
    public function setTokenCompletionTime(Token $token, ?DateTimeInterface $completionTime, int $surveyId, int|string|null $sourceSurveyId = null): void
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
        $current   = new Expression('CURRENT_TIMESTAMP');

        if ($completionTime instanceof DateTimeInterface) {
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

        } elseif ($completionTime instanceof DateTimeInterface) {
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

        } elseif ($completionTime instanceof DateTimeImmutable) {

            $tokenData['token'] = $lsTokenId;
            $tokenData = $tokenData + $this->_fillAttributeMap($token);

            $insert = $sql->insert($lsTabTok)->values($tokenData);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }
        $token->cacheReset();
    }

    /**
     * Updates the consent code of the token in the source (if needed)
     *
     * @param Token $token
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @param ?string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(Token $token, int $surveyId, int|string|null $sourceSurveyId = null, ?string $consentCode = null): int
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsAdapter = $this->getSourceDatabase();
        $lsResultFetcher = $this->getSourceResultFetcher();
        $lsTokens = $this->_getTokenTableName($sourceSurveyId);
        $tokenId  = $this->_getToken($token->getTokenId());

        if (null === $consentCode) {
            $consentCode = $token->getConsentCode();
        }
        $values[$this->_attributeMap['consentcode']] = $consentCode;

        if ($oldValues = $lsResultFetcher->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", [$tokenId])) {

            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                if (Tracker::$verbose) {
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

                    return 1;
                }
            }
        }

        return 0;
    }

    /**
     * Get the table structure of a survey table
     *
     * @param int|string $sourceSurveyId Limesurvey survey ID
     * @return array List of table structure
     */
    public function getSurveyTableStructure(int|string $sourceSurveyId): array
    {
        $tableStructure = $this->_getFieldMap($sourceSurveyId)->getSurveyTableStructure();

        return $tableStructure;
    }

    /**
     * Get the table structure of a survey token table
     *
     * @param int|string $sourceSurveyId Limesurvey survey ID
     * @return array List of table structure of survey token table
     */
    public function getTokenTableStructure(int|string $sourceSurveyId): array
    {
        $tableStructure = $this->_getFieldMap($sourceSurveyId)->getTokenTableStructure();

        return $tableStructure;
    }

    /**
     * Execute a Database query on the limesurvey Database
     *
     * @param int|string $sourceSurveyId Limesurvey survey ID
     * @param mixed $sql SQL query to perform on the limesurvey database
     * @param ?array $bindValues optional bind values for the Query
     */
    public function lsDbQuery(int|string $sourceSurveyId, mixed $sql, ?array $bindValues=[]): StatementInterface|ResultSet
    {
        return $this->_getFieldMap($sourceSurveyId)->lsDbQuery($sql, $bindValues);
    }

    /**
     * Return data indexed by the value of the Field key.
     *
     * @param array $tableData
     * @return array
     */
    private function indexByField(array $tableData): array
    {
        $newData = [];
        foreach ($tableData as $index => $row) {
            $newIndex = $row['Field'];
            $newData[$newIndex] = $row;
        }

        return $newData;
    }
}
