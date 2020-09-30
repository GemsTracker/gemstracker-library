<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * LimeSurvey1m9Database is a Source interface that enables the use of LimeSurvey 1.9.x
 * installation as survey/answer source for Gems projects.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Tracker_Source_LimeSurvey1m9Database extends \Gems_Tracker_Source_SourceAbstract
{
    const CACHE_TOKEN_INFO = 'tokenInfo';

    const LS_DB_COMPLETION_FORMAT = 'yyyy-MM-dd HH:mm';
    const LS_DB_DATE_FORMAT       = 'yyyy-MM-dd';
    const LS_DB_DATETIME_FORMAT   = 'yyyy-MM-dd HH:mm:ss';

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
    ];

    /**
     *
     * @var string The LS version dependent field name for anonymized surveys
     */
    protected $_anonymizedField = 'private';

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
     * @var array of \Gems_Tracker_Source_LimeSurvey1m9FieldMap
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
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var string class name for creating field maps
     */
    protected $fieldMapClass = '\Gems_Tracker_Source_LimeSurvey1m9FieldMap';

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems_Log
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
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Checks the return URI in LimeSurvey and sets it to the correct one when needed
     *
     * @see checkSurvey()
     *
     * @param string $sourceSurveyId
     * @param \Gems_Tracker_Survey $survey
     * @param array $messages
     */
    protected function _checkReturnURI($sourceSurveyId, \Gems_Tracker_Survey $survey, array &$messages)
    {
        $lsSurvLang = $this->_getSurveyLanguagesTableName();
        $sql = 'SELECT surveyls_language FROM ' . $lsSurvLang . ' WHERE surveyls_survey_id = ?';

        $lsDb = $this->getSourceDatabase();

        $languages = $lsDb->fetchAll($sql, array($sourceSurveyId));
        $langChanges = 0;
        foreach ($languages as $language)
        {
            $langChanges = $langChanges + $lsDb->update(
                $lsSurvLang,
                array(
                    'surveyls_urldescription' => $this->_getReturnURIDescription($language['surveyls_language'])
                    ),
                array(
                    'surveyls_survey_id = ?' => $sourceSurveyId,
                    'surveyls_language = ?'  => $language
                    )
                );
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

        return $missingFields;
    }

    /**
     *
     * @param string $typeDescr E.g. int(11) or varchar(36)
     * @return int In case 11 or 36
     */
    private function _extractFieldLength($typeDescr)
    {
        $lengths = array();
        if (preg_match('/\(([^\)]+)\)/', $typeDescr, $lengths)) {
            return $lengths[1];
        }

        return $this->attributeSize;    // When type is text there is no size
    }

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(\Gems_Tracker_Token $token)
    {
        $values[$this->_attributeMap['respondentid']]   =
                substr($token->getRespondentId(), 0, $this->attributeSize);
        $values[$this->_attributeMap['organizationid']] =
                substr($token->getOrganizationId(), 0, $this->attributeSize);
        $values[$this->_attributeMap['consentcode']]    =
                substr($token->getConsentCode(), 0, $this->attributeSize);
        $values[$this->_attributeMap['resptrackid']]    =
                substr($token->getRespondentTrackId(), 0, $this->attributeSize);

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
     * @return \Gems_Tracker_Source_LimeSurvey1m9FieldMap
     */
    protected function _getFieldMap($sourceSurveyId, $language = null)
    {
        $language = $this->_getLanguage($sourceSurveyId, $language);
        // \MUtil_Echo::track($language, $sourceSurveyId);

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
                $lsDb = $this->getSourceDatabase();

                $sql = 'SELECT language
                    FROM ' . $this->_getSurveysTableName() . '
                    WHERE sid = ?';

                $this->_languageMap[$sourceSurveyId][$language] = $lsDb->fetchOne($sql, $sourceSurveyId);
            }
        }

        return $this->_languageMap[$sourceSurveyId][$language];
    }

    /**
     * Get the return URI to return from LimeSurvey to GemsTracker
     *
     * @return string
     */
    protected function _getReturnURI()
    {
        return $this->util->getCurrentURI('ask/return/' . \MUtil_Model::REQUEST_ID . '/{TOKEN}');
    }

    /**
     * Get the return URI description to set in LimeSurvey
     *
     * @param string $language
     * @return string
     */
    protected function _getReturnURIDescription($language)
    {
        return sprintf(
            $this->translate->_('Back to %s', $language),
            $this->project->getName()
        );
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
        $lsDb = $this->getSourceDatabase();

        $select = $lsDb->select();
        $select->from($this->_getSurveysTableName(), 'sid')
                ->order('sid');

        return $lsDb->fetchCol($select);
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
     * @param string $token
     * @param boolean $reverse  Reverse the action to go from limesurvey to GemsTracker token (default is false)
     * @return string
     */
    protected function _getToken($tokenId, $reverse = false)
    {
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
    protected function _isLanguage($sourceSurveyId, $language)
    {
        if ($language && strlen($language)) {
            // Check for availability of language
            $sql = 'SELECT surveyls_language FROM ' . $this->_getSurveyLanguagesTableName() . ' WHERE surveyls_survey_id = ? AND surveyls_language = ?';
            $lsDb = $this->getSourceDatabase();

            return $lsDb->fetchOne($sql, array($sourceSurveyId, $language));
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
        $tables    = array_map('strtolower', $sourceDb->listTables());
        $tableName = $this->addDatabasePrefix(self::SURVEYS_TABLE, false); // Get name without database prefix.

        $active = strtolower(in_array($tableName, $tables));

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
            $lsDb = $this->getSourceDatabase();

            // SELECT sid, surveyls_title AS short_title, surveyls_description AS description, active, datestamp, ' . $this->_anonymizedField . '
            $select = $lsDb->select();
            // 'alloweditaftercompletion' ?
            $select->from($this->_getSurveysTableName(), array('active', 'datestamp', 'language', 'additional_languages', 'autoredirect', 'alloweditaftercompletion', 'allowregister', 'listpublic', 'tokenanswerspersistence', 'expires', $this->_anonymizedField))
                    ->joinInner(
                            $this->_getSurveyLanguagesTableName(),
                            'sid = surveyls_survey_id AND language = surveyls_language',
                            array('surveyls_title', 'surveyls_description'))
                    ->where('sid = ?', $sourceSurveyId);
            $lsSurvey = $lsDb->fetchRow($select);

            $surveyor_title = substr(\MUtil_Html::removeMarkup(html_entity_decode($lsSurvey['surveyls_title'])), 0, 100);
            $surveyor_description = substr(\MUtil_Html::removeMarkup(html_entity_decode($lsSurvey['surveyls_description'])), 0, 100);
            $surveyor_status = '';
            $surveyor_warnings = '';
            
            // AVAILABLE LANGUAGES
            $surveyor_languages = substr(\MUtil_Html::removeMarkup(html_entity_decode($lsSurvey['language'])), 0, 100);
            $surveyor_additional_languages = substr(\MUtil_Html::removeMarkup(html_entity_decode($lsSurvey['additional_languages'])), 0, 100);
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
                    $lsDb->query("UPDATE " . $this->_getSurveysTableName() . " SET `" . $this->_anonymizedField . "` = 'N' WHERE sid = ?;", $sourceSurveyId);
                    $messages[] = sprintf($this->_("Corrected anonymization for survey '%s'"), $surveyor_title);

                    $lsDb->query("ALTER TABLE " . $this->_getSurveyTableName($sourceSurveyId) . " ADD `token` varchar(36) default NULL;");
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
                    $tokenTable = $lsDb->fetchAssoc('SHOW COLUMNS FROM ' . $this->_getTokenTableName($sourceSurveyId));
                } catch (\Zend_Exception $e) {
                    $tokenTable = false;
                }

                if ($tokenTable) {
                    $missingFields   = $this->_checkTokenTable($tokenTable);

                    if ($missingFields) {
                        $sql    = "ALTER TABLE " . $this->_getTokenTableName($sourceSurveyId) . " " . implode(', ', $missingFields);
                        $fields = implode($this->_(', '), array_keys($missingFields));
                        // \MUtil_Echo::track($missingFields, $sql);
                        try {
                            $lsDb->query($sql);
                            $messages[] = sprintf($this->_("Added to token table '%s' the field(s): %s"), $surveyor_title, $fields);
                        } catch (\Zend_Exception $e) {
                            $surveyor_status .= 'Token attributes could not be created. ';
                            $surveyor_status .= $e->getMessage() . ' ';

                            $messages[] = sprintf($this->_("Attribute fields not created for token table for '%s'"), $surveyor_title);
                            $messages[] = sprintf($this->_('Required fields: %s', $fields));
                            $messages[] = $e->getMessage();

                            // Maximum reporting for this case
                            \MUtil_Echo::r($missingFields, 'Missing fields for ' . $surveyor_title);
                            \MUtil_Echo::r($e);
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

            // Update Gems
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

                if (substr($surveyor_status,  0,  127) != (string) $survey->getStatus()) {
                    if ($surveyor_status) {
                        $values['gsu_status'] = substr($surveyor_status,  0,  127);
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
     * @param \Gems_Tracker_Token $token
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems_Tracker_Source_SurveyNotFoundException
     */
    public function copyTokenToSource(\Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $language   = $this->_getLanguage($sourceSurveyId, $language);
        $lsDb       = $this->getSourceDatabase();
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
        $currentUrl = $lsDb->fetchOne($sql, array($sourceSurveyId, $language));

        // No field was returned
        if (false === $currentUrl) {
            throw new \Gems_Tracker_Source_SurveyNotFoundException(sprintf('The survey with id %d for token %s does not exist.', $surveyId, $tokenId), sprintf('The Lime Survey id is %s', $sourceSurveyId));
        }

        /*****************************
         * Set the end_of_survey uri *
         *****************************/

        if (!\MUtil_Console::isConsole()) {
            $newUrl = $this->_getReturnURI();

            // Make sure the url is set correctly in surveyor.
            if ($currentUrl != $newUrl) {

                //$where = $lsDb->quoteInto('surveyls_survey_id = ? AND ', $sourceSurveyId) .
                //    $lsDb->quoteInto('surveyls_language = ?', $language);

                $lsDb->update($lsSurvLang,
                    array('surveyls_url' => $newUrl),
                    array(
                        'surveyls_survey_id = ?' => $sourceSurveyId,
                        'surveyls_language = ?' =>  $language
                        ));

                if (\Gems_Tracker::$verbose) {
                    \MUtil_Echo::r("From $currentUrl\n to $newUrl", "Changed return url for $language version of $surveyId.");
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
        if ($token->isCompleted()) {
            $values['completed'] = $token->getCompletionTime()->toString(self::LS_DB_COMPLETION_FORMAT);
        } else {
            $values['completed'] = 'N';
        }

        $result = 0;
        if ($oldValues = $lsDb->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", $tokenId)) {

            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                if (\Gems_Tracker::$verbose) {
                    $echo = '';
                    foreach ($values as $key => $val) {
                        $echo .= $key . ': ' . $oldValues[$key] . ' => ' . $val . "\n";
                    }
                    \MUtil_Echo::r($echo, "Updated limesurvey values for $tokenId");
                }

                $result = $lsDb->update($lsTokens, $values, array('token = ?' => $tokenId));
            }
        } else {
            if (\Gems_Tracker::$verbose) {
                \MUtil_Echo::r($values, "Inserted $tokenId into limesurvey");
            }
            $values['token'] = $tokenId;

            $result = $lsDb->insert($lsTokens, $values);
        }

        if ($result) {
            //If we have changed something, invalidate the cache
            $token->cacheReset();
        }

        return $result;
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
        $lsDb = $this->getSourceDatabase();

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

        return (boolean) $lsDb->update($this->_getSurveysTableName(), $fields, $lsDb->quoteInto('sid = ?', $sourceSurveyId));
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems_Tracker_Token  $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName, \Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $answers = $token->getRawAnswers();

        if (isset($answers[$fieldName]) && $answers[$fieldName]) {
            return \MUtil_Date::ifDate(
                    $answers[$fieldName],
                    array(self::LS_DB_DATETIME_FORMAT, self::LS_DB_DATE_FORMAT)
                    );
        }
    }

    public function getAttributes()
    {
        return array_keys($this->_attributeMap);
    }

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems_Tracker_Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Date date time or null
     */
    public function getCompletionTime(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
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

                    $lsDb       = $this->getSourceDatabase();
                    $lsSurvey   = $this->_getSurveyTableName($sourceSurveyId);
                    $tokenId    = $this->_getToken($token->getTokenId());

                    $submitDate = $lsDb->fetchOne("SELECT submitdate FROM $lsSurvey WHERE token = ? LIMIT 1", $tokenId);

                    if ($submitDate) {
                        $submitDate = \MUtil_Date::ifDate($submitDate, self::LS_DB_DATETIME_FORMAT);
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

        return $submitDate instanceof \MUtil_Date ? $submitDate : null;
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
        $lsDb       = $this->getSourceDatabase();
        $lsToken   = $this->_getTokenTableName($sourceSurveyId);

        // Make sure we use tokens in the format LimeSurvey likes
        $tokens = array_map(array($this, '_getToken'), $tokenIds);

        $sql = $lsDb->select()
                ->from($lsToken, array('token'))
                ->where('token IN (?)', $tokens)
                ->where('completed != ?', 'N');

        $completedTokens = $lsDb->fetchCol($sql);

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
     * @param int $surveyId Gems Survey Id
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
            $value = \MUtil_Html::raw($value);
        }

        return $results;
    }

    /**
     * Returns an array containing fieldname => label for each answerable question in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     * @deprecated since version 1.8.4 remove in 1.8.5
     */
    public function getFullQuestionList($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        return $this->_getFieldMap($sourceSurveyId, $language)->getFullQuestionList();
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
     * @param int $surveyId Gems Survey Id
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
     * @param int $surveyId Gems Survey Id
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
     * @param string $tokenId Gems Token Id
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb   = $this->getSourceDatabase();
        $lsTab  = $this->_getSurveyTableName($sourceSurveyId);
        $token  = $this->_getToken($tokenId);

        try {
            // Order by ID desc to get the same answers used as in the row retrieved by
            // getRawTokenAnswerRows() in case of double rows
            $values = $lsDb->fetchRow("SELECT * FROM $lsTab WHERE token = ? ORDER BY id DESC", $token);
        } catch (\Zend_Db_Statement_Exception $exception) {
            $this->logger->logError($exception, $this->request);
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
     * @param int $surveyId Gems Survey Id
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

        // Prevent failure when survey no longer active
        try {
            $rows = $select->query()->fetchAll(\Zend_Db::FETCH_ASSOC);
        } catch (Exception $exc) {
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
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int
     */
    public function getRawTokenAnswerRowsCount(array $filter, $surveyId, $sourceSurveyId = null) {
        $select = $this->getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);

        $p = new \Zend_Paginator_Adapter_DbSelect($select);
        $count = $p->getCountSelect()->query()->fetchColumn();

        return $count;
    }

    /**
     * Get the select object to use for RawTokenAnswerRows
     *
     * @param array $filter
     * @param type $surveyId
     * @param type $sourceSurveyId
     * @return \Zend_Db_Select
     */
    public function getRawTokenAnswerRowsSelect(array $filter, $surveyId, $sourceSurveyId = null) {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb = $this->getSourceDatabase();
        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenTable  = $this->_getTokenTableName($sourceSurveyId);
        $tokenField    = $lsSurveyTable . '.token';

        $quotedTokenTable  = $lsDb->quoteIdentifier($lsTokenTable . '.token');
        $quotedSurveyTable = $lsDb->quoteIdentifier($lsSurveyTable . '.token');

        $select = $lsDb->select();
        $select->from($lsTokenTable, $this->_attributeMap)
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
            $field = $lsDb->quoteIdentifier($field);
            if (is_array($values)) {
                $select->where("$field IN (?)", array_values($values));
            } else {
                $select->where("$field = ?", $values);
            }
        }

        if (\Gems_Tracker::$verbose) {
            \MUtil_Echo::r($select->__toString(), 'Select');
        }

        return $select;
    }

    /**
     * Gets the time the survey was started according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems_Tracker_Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Date date time or null
     */
    public function getStartTime(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        // Always return null!
        // The 'startdate' field is the time of the first save, not the time the user started
        // so Lime Survey does not contain this value.
        return null;
    }

    /**
     * Returns a model for the survey answers
     *
     * @param \Gems_Tracker_Survey $survey
     * @param string $language Optional (ISO) language string
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil_Model_ModelAbstract
     */
    public function getSurveyAnswerModel(\Gems_Tracker_Survey $survey, $language = null, $sourceSurveyId = null)
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
     * @param \Gems_Tracker_Token $token
     * @param type $surveyId
     * @param type $sourceSurveyId
     * @param array $fields
     * @return type
     */
    public function getTokenInfo(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId, array $fields = null)
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
                $result = $this->getSourceDatabase()->fetchRow($sql, $tokenId);
            } catch (\Zend_Db_Statement_Exception $exception) {
                $this->logger->logError($exception, $this->request);
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
     * @param \Gems_Tracker_Token $token Gems token object
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }
        $tokenId = $this->_getToken($token->getTokenId());

        if ($this->_isLanguage($sourceSurveyId, $language)) {
            $langUrl = '&lang=' . $language;
        } else {
            $langUrl = '';
        }

        // mgzdev.erasmusmc.nl/incant/index.php?sid=1&token=o7l9_b8z2
        $baseurl = $this->getBaseUrl();
        return $baseurl . ('/' == substr($baseurl, -1) ? '' : '/') . 'index.php?sid=' . $sourceSurveyId . '&token=' . $tokenId . $langUrl;
    }
    
    /**
     * Get valid from/to dates to send to LimeSurvey depending on the dates of the token
     * 
     * @param \Gems_Tracker_Token $token
     * @return []
     */
    public function getValidDates(\Gems_Tracker_Token $token)
    {
        $now = new \MUtil_Date();
        // For extra protection, we add valid from/to dates as needed instead of leaving them in GemsTracker only
        $tokenFrom  = $token->getValidFrom();
        $tokenUntil = $token->getValidUntil();
        // Always set a date, so LimeSurvey will check the token
        $lsFrom     = is_null($tokenFrom) ? new \MUtil_Date('1900-01-01') : $tokenFrom;
        if (!is_null($tokenUntil) && $tokenUntil->isEarlier($now)) {
            $lsUntil = $tokenUntil;
        } elseif (!is_null($tokenFrom)) {
            // To end of day. If entering via GemsTracker it will always be updated as long as the token is still valid
            $lsUntil = clone $now;
            $lsUntil->setTimeToDayEnd();
        } else {
            // No valid from date, use same date as until
            $lsUntil = $lsFrom;
        }

        $values = [
            'validfrom'  => $lsFrom->toString(self::LS_DB_DATETIME_FORMAT),
            'validuntil' => $lsUntil->toString(self::LS_DB_DATETIME_FORMAT)
        ];

        return $values;
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);
        return (boolean) $tokenInfo;
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param $answers array Field => Value array, can be empty
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
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
     * @param \Gems_Tracker_Token $token Gems token object
     * @param array $answers Field => Value array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return true When answers changed
     */
    public function setRawTokenAnswers(\Gems_Tracker_Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb      = $this->getSourceDatabase();
        $lsTab     = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenId = $this->_getToken($token->getTokenId());

        // \MUtil_Echo::track($answers);

        $answers = $this->_getFieldMap($sourceSurveyId)->mapTitlesToKeys($answers);
        $answers = $this->_filterAnswersOnly($sourceSurveyId, $answers);

        // \MUtil_Echo::track($answers);

        if ($lsDb->fetchOne("SELECT token FROM $lsTab WHERE token = ?", $lsTokenId)) {
            $where = $lsDb->quoteInto("token = ?", $lsTokenId);
            if ($answers) {
                return $lsDb->update($lsTab, $answers, $where);
            }
        } else {
            $current = new \MUtil_Db_Expr_CurrentTimestamp();

            $answers['token']         = $lsTokenId;
            $answers['startlanguage'] = $this->locale->getLanguage();
            $answers['datestamp']     = $current;
            $answers['startdate']     = $current;

            $lsDb->insert($lsTab, $answers);

            return true;
        }
    }

    /**
     * Sets the completion time.
     *
     * @param \Gems_Tracker_Token $token Gems token object
     * @param \Zend_Date|null $completionTime \Zend_Date or null
     * @param int $surveyId Gems Survey Id (actually required)
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setTokenCompletionTime(\Gems_Tracker_Token $token, $completionTime, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb      = $this->getSourceDatabase();
        $lsTabSurv = $this->_getSurveyTableName($sourceSurveyId);
        $lsTabTok  = $this->_getTokenTableName($sourceSurveyId);
        $lsTokenId = $this->_getToken($token->getTokenId());
        $where     = $lsDb->quoteInto("token = ?", $lsTokenId);
        $current   = new \MUtil_Db_Expr_CurrentTimestamp();

        if ($completionTime instanceof \Zend_Date) {
            $answers['submitdate']  = $completionTime->toString(self::LS_DB_DATETIME_FORMAT);
            $tokenData['completed'] = $completionTime->toString(self::LS_DB_COMPLETION_FORMAT);
        } else {
            $answers['submitdate']  = null;
            $tokenData['completed'] = 'N';
        }

        // Set for the survey
        if ($lsDb->fetchOne("SELECT token FROM $lsTabSurv WHERE token = ?", $lsTokenId)) {
            $lsDb->update($lsTabSurv, $answers, $where);

        } elseif ($completionTime instanceof \Zend_Date) {
            $answers['token']         = $lsTokenId;
            $answers['startlanguage'] = $this->locale->getLanguage();
            $answers['datestamp']     = $current;
            $answers['startdate']     = $current;

            $lsDb->insert($lsTabSurv, $answers);
        }

        // Set for the token
        if ($lsDb->fetchOne("SELECT token FROM $lsTabTok WHERE token = ?", $lsTokenId)) {
            $lsDb->update($lsTabTok, $tokenData, $where);

        } elseif ($completionTime instanceof \Zend_Date) {

            $tokenData['token'] = $lsTokenId;
            $tokenData = $tokenData + $this->_fillAttributeMap($token);

            $lsDb->insert($lsTabTok, $tokenData);
        }
        $token->cacheReset();
    }

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     * /
    public function synchronizeSurveys($userId)
    {
        // Surveys in LS
        $lsDb = $this->getSourceDatabase();
        $select = $lsDb->select();
        $select->from($this->_getSurveysTableName(), 'sid')
                ->order('sid');
        $lsSurveys = $lsDb->fetchCol($select);

        if (!$lsSurveys) {
            //If no surveys present, just use an empty array as array_combine fails
            $lsSurveys = array();
        } else {
            $lsSurveys = array_combine((array) $lsSurveys, (array) $lsSurveys);
        }

        // Surveys in Gems
        $gemsSurveys = $this->_getGemsSurveysForSynchronisation();

        foreach ($gemsSurveys as $surveyId => $sourceSurveyId) {
            if (isset($lsSurveys[$sourceSurveyId])) {
                if ($this->hasBatch()) {
                    $this->_batch->addTask('Tracker_SourceCommand', $this->getId(), 'CheckSurvey', $sourceSurveyId, $surveyId, $userId);
                } else {
                    $this->checkSurvey($sourceSurveyId, $surveyId, $userId);
                }
            } else {
                if ($this->hasBatch()) {
                    $this->_batch->addTask('Tracker_SourceCommand', $this->getId(), 'CheckSurvey', null, $surveyId, $userId);
                } else {
                    $this->checkSurvey(null, $surveyId, $userId);
                }
            }
        }

        foreach (array_diff($lsSurveys, $gemsSurveys) as $sourceSurveyId) {
            if ($this->hasBatch()) {
                $this->_batch->addTask('Tracker_SourceCommand', $this->getId(), 'CheckSurvey', $sourceSurveyId, null, $userId);
            } else {
                $this->checkSurvey($sourceSurveyId, null, $userId);
            }
        }
    }

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @param bool $updateTokens Wether the tokens should be updated or not, default is true
     * @return array Returns an array of messages
     * /
    public function synchronizeSurveysOld($userId, $updateTokens = true)
    {
        $lsDb          = $this->getSourceDatabase();
        $messages      = array();
        $source_id     = $this->getId();
        $surveys_table = $this->_getSurveysTableName();
        $s_langs_table = $this->_getSurveyLanguagesTableName();
        $token_library = $this->tracker->getTokenLibrary();

        if ($updateTokens) {
            if ($count = $this->updateTokens($userId)) {
                $messages[] = sprintf($this->_('Updated %d Gems tokens to new token definition.'), $count);
            }
        }

        $sql = '
            SELECT sid, surveyls_title AS short_title, surveyls_description AS description, active, datestamp, ' . $this->_anonymizedField . '
                FROM ' . $surveys_table . ' INNER JOIN ' . $s_langs_table . '
                    ON sid = surveyls_survey_id AND language = surveyls_language
                ORDER BY surveyls_title';

        $surveyor_surveys = $lsDb->fetchAssoc($sql);

        ////////////////////////////////////////////////////
        // First check for surveys that have disappeared. //
        ////////////////////////////////////////////////////
        if ($surveyor_surveys) {
            // Get the first elements in an array
            $surveyor_sids = array_map('reset', $surveyor_surveys);

            foreach($this->_updateGemsSurveyExists($surveyor_sids, $userId) as $surveyId => $title) {
                $messages[] = sprintf($this->_('The \'%s\' survey is no longer active. The survey was removed from LimeSurvey!'), $title);
            }
        }

        ////////////////////////////////////
        // Check for updates and inserts. //
        ////////////////////////////////////
        foreach ($surveyor_surveys as $surveyor_survey)
        {
            $sid =  $surveyor_survey['sid'];

            $survey = $this->tracker->getSurveyBySourceId($sid, $source_id);

            $surveyor_status = '';
            if ($surveyor_survey[$this->_anonymizedField] == 'Y') {
                $surveyor_status .= 'Uses anonymous answers. ';
            } elseif ($surveyor_survey[$this->_anonymizedField] !== 'N') {
                // This is for the case that $this->_anonymizedField is empty, we show an update statement.
                // The answers already in the table can only be linked to the repsonse based on the completion time
                // this requires a manual action as token table only hold minuts while survey table holds seconds
                // and we might have responses with the same timestamp.
                $update = "UPDATE " . $this->_getSurveysTableName() . " SET `" . $this->_anonymizedField . "` = 'N' WHERE sid = " . $sid . ';';
                $update .= "ALTER TABLE " . $this->_getSurveyTableName($sid) . " ADD `token` varchar(36) default NULL;";
                \MUtil_Echo::r($update);
            }
            if ($surveyor_survey['datestamp'] == 'N') {
                $surveyor_status .= 'Not date stamped. ';
            }
            if ($surveyor_survey['active'] == 'Y') {
                // I needed this code once to restore from an error, left it in just in case
                //
                // Restore tokens, set attribute_1 to gto_id_respondent_track
                /*
                $sql = 'SELECT count(*) FROM ' . $this->getSurveyTableName($sid);
                if ($lsDb->fetchOne($sql)) {
                    $sql = 'SELECT gto_id_token, gto_id_respondent, gto_id_organization, gto_completion_time
                        FROM gems__tokens INNER JOIN gems__surveys ON gto_id_survey = gsu_id_survey
                        WHERE gto_in_source = 1 AND gsu_surveyor_id = ?
                        ORDER BY gto_id_organization, gto_completion_time';

                    $gemsTokens = $this->_gemsDb->fetchAll($sql, $sid);

                    $sql = 'SELECT tid, attribute_1, attribute_2, completed
                        FROM ' . $this->getTokenTableName($sid) . '
                        ORDER BY attribute_2, completed';

                    $lsTokens = $lsDb->fetchAll($sql);

                    $gemsToken = reset($gemsTokens);
                    foreach ($lsTokens as $lsToken) {

                        $tokenData = array('token' => $this->limesurveyToken($gemsToken['gto_id_token']));
                        $lsDb->update($this->getSurveyTableName($sid), $tokenData, array('id = ?' => $lsToken['tid']));

                        // $token['attribute_1'] = $gemsToken['gto_id_respondent'];
                        $lsDb->update($this->getTokenTableName($sid), $tokenData, array('tid = ?' => $lsToken['tid']));

                        $gemsToken = next($gemsTokens);
                    }
                } // * /

                $surveyor_active = true;
                try {
                    $sql = 'SHOW COLUMNS FROM ' . $this->_getTokenTableName($sid);
                    $tokenTable = $lsDb->fetchAssoc($sql);

                    if ($tokenTable) {
                        $lengths = array();
                        if (preg_match('/\(([^\)]+)\)/', $tokenTable['token']['Type'], $lengths)) {
                            $tokenLength = $lengths[1];
                        } else {
                            $tokenLength = 0;
                        }
                        if ($tokenLength < $token_library->getLength()) {
                            $surveyor_status .= 'Token field length is too short. ';
                        }

                        $missingFields = array();
                        foreach ($this->_attributeMap as $name => $field) {
                            if (! isset($tokenTable[$field])) {
                                $missingFields[] = "ADD $field varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
                            }
                        }
                        if ($missingFields) {
                            $sql = "ALTER TABLE " . $this->_getTokenTableName($sid) . " " . implode(', ', $missingFields);
                            // \MUtil_Echo::track($sql);
                            try {
                                $lsDb->query($sql);
                            } catch (\Zend_Exception $e) {
                                $surveyor_status .= 'Token attributes could not be created. ';
                                $surveyor_status .= $e->getMessage() . ' ';
                                // \MUtil_Echo::track($e);
                            }
                        }
                        /*
                        $attrCount = 0;
                        for ($i = 1; $i < 10; $i++) {
                            $attrFields[$i] = isset($tokenTable['attribute_' . $i]);
                            $attrCount += $attrFields[$i] ? 1 : 0;
                        }
                        $neededAttr = (3 - $attrCount);
                        if ($neededAttr > 0) {
                            // Not enough attribute fields
                            if (1 == $neededAttr) {
                                $surveyor_status .= '1 extra token attribute field required. ';
                            } else {
                                $surveyor_status .= $neededAttr . ' extra token attribute fields required. ';
                            }
                        } else {
                            // Are the names OK
                            for ($i = 1; $i < 4; $i++) {
                                if (!$attrFields[$i]) {
                                    $surveyor_status .= sprintf('Token attribute field %d is missing. ', $i);
                                }
                            }
                        } // * /
                    }

                    if ($updateTokens && (! $surveyor_status)) {
                        // Check for changes in the token definitions and for
                        // Gems tokens that should be LS tokens ('_' instead of '-')
                        $from = $token_library->getFrom() . '-';

                        $sqlTail = ' SET `token` = ' . $this->_getTokenFromToSql($from, $token_library->getTo() . '_', 'token') .
                            ' WHERE ' . $this->_getTokenFromSqlWhere($from, 'token');

                        $sql = 'UPDATE ' . $this->_getTokenTableName($sid) . $sqlTail;
                        // \MUtil_Echo::pre($sql);

                        if ($count = $lsDb->query($sql)->rowCount()) {
                            // Only update surveys table if there were tokens
                            $sql = 'UPDATE ' . $this->_getSurveyTableName($sid) . $sqlTail;
                            $lsDb->query($sql);
                            // \MUtil_Echo::pre($sql);

                            $messages[] = sprintf($this->plural('Updated %d token to new token definition in survey \'%s\'.', 'Updated %d tokens to new token definition in survey \'%s\'.', $count), $count, $survey->getName());
                        }
                    }
                } catch (\Zend_Exception $e) {
                    $surveyor_status .= 'No token table created. ';
                }

            } else {
                $surveyor_active = false;
                $surveyor_status .= 'Not active. ';
            }
            $surveyor_title = substr($surveyor_survey['short_title'], 0, 100);
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

                if (substr($surveyor_status,  0,  127) != (string) $survey->getStatus()) {
                    if ($surveyor_status) {
                        $values['gsu_status'] = substr($surveyor_status,  0,  127);
                        $messages[] = sprintf($this->_('The status of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $values['gsu_status']);
                    } else {
                        $values['gsu_status'] = new \Zend_Db_Expr('NULL');
                        $messages[] = sprintf($this->_('The status warning for the \'%s\' survey was removed.'), $survey->getName());
                    }
                }

                if ($survey->getName() != $surveyor_title) {
                    $values['gsu_survey_name'] = $surveyor_title;
                    $messages[] = sprintf($this->_('The name of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_title);
                }

            } else { // New record
                $values['gsu_survey_name']        = $surveyor_title;
                $values['gsu_survey_description'] = strtr(substr($surveyor_survey['description'], 0, 100), "\xA0\xC2", '  ');
                $values['gsu_surveyor_active']    = $surveyor_active ? 1 : 0;
                $values['gsu_active']             = 0;
                $values['gsu_status']             = $surveyor_status;

                $messages[] = sprintf($this->_('Imported the \'%s\' survey.'), $surveyor_title);
            }
            $survey->saveSurvey($values, $userId);
        }

        // TODO: check for token field in survey table.

        return $messages;
    } // */

    /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param \Gems_Tracker_Token $token
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(\Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb     = $this->getSourceDatabase();
        $lsTokens = $this->_getTokenTableName($sourceSurveyId);
        $tokenId  = $this->_getToken($token->getTokenId());

        if (null === $consentCode) {
            $consentCode = (string) $token->getConsentCode();
        }
        $values[$this->_attributeMap['consentcode']] = $consentCode;

        if ($oldValues = $lsDb->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", $tokenId)) {

            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                if (\Gems_Tracker::$verbose) {
                    $echo = '';
                    foreach ($values as $key => $val) {
                        $echo .= $key . ': ' . $oldValues[$key] . ' => ' . $val . "\n";
                    }
                    \MUtil_Echo::r($echo, "Updated limesurvey values for $tokenId");
                }

                $result = $lsDb->update($lsTokens, $values, array('token = ?' => $tokenId));

                if ($result) {
                    //If we have changed something, invalidate the cache
                    $token->cacheReset('tokenInfo');
                }
                return $result;
            }
        }

        return 0;
    }
}