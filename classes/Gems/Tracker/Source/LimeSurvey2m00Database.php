<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Difference with 1.9 version:
 *   - private field was renamed to anonymized
 *   - url for survey was changed
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Tracker_Source_LimeSurvey2m00Database extends \Gems_Tracker_Source_LimeSurvey1m91Database
{
    /**
     *
     * @var string class name for creating field maps
     */
    protected $fieldMapClass = '\Gems_Tracker_Source_LimeSurvey2m00FieldMap';

    /**
     * Check a token table for any changes needed by this version.
     *
     * @param array $tokenTable
     * @return array Fieldname => change field commands
     */
    protected function _checkTokenTable(array $tokenTable)
    {
        $missingFields = parent::_checkTokenTable($tokenTable);

        return self::addnewAttributeFields($tokenTable, $missingFields);
    }

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * Adds the fields without default new in 2.00
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(\Gems_Tracker_Token $token)
    {
        $values = parent::_fillAttributeMap($token);

        return self::addnewAttributeDefaults($values);
    }

    /**
     * Adds the fields without default new in 2.00
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of fieldname => value type
     */
    public static function addnewAttributeDefaults(array $values)
    {
        // Not really attributes, but they need a value
        $values['participant_id'] = '';
        $values['blacklisted']    = '';

        return $values;
    }

    /**
     * Adds the fields without default new in 2.00
     *
     * @param array $tokenTable
     * @param array $missingFields
     * @return array Fieldname => change field commands
     */
    public static function addnewAttributeFields(array $tokenTable, array $missingFields)
    {
        if (! isset($tokenTable['participant_id'])) {
            $missingFields['participant_id'] = "ADD participant_id varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL";
        }
        if (! isset($tokenTable['blacklisted'])) {
            $missingFields['blacklisted'] = "ADD blacklisted varchar(17) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL";
        }

        return $missingFields;
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
            $langUrl = '/lang/' . $language;
        } else {
            $langUrl = '';
        }

        // <base>/index.php/survey/index/sid/834486/token/234/lang/en
        $baseurl = $this->getBaseUrl();
        $start = $baseurl . ('/' == substr($baseurl, -1) ? '' : '/');
        if (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false || (ini_get('security.limit_extensions') && ini_get('security.limit_extensions')!='')) {
            // Apache : index.php/
            $start .= 'index.php/';
        } else {
            $start .= 'index.php?r=';
        }
        return $start . 'survey/index/sid/' . $sourceSurveyId . '/token/' . $tokenId . $langUrl . '/newtest/Y';
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