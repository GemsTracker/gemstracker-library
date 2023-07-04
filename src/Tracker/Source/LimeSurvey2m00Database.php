<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

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
class LimeSurvey2m00Database extends \Gems\Tracker\Source\LimeSurvey1m91Database
{
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
