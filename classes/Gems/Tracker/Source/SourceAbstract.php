<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Abstract implementation of SourceInterface containing basic utilities and logical
 * separation between the Gems database and the Source database
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class Gems_Tracker_Source_SourceAbstract extends MUtil_Translate_TranslateableAbstract
    implements Gems_Tracker_Source_SourceInterface
{
    /**
     * Holds the current batch if there is any
     *
     * @var Gems_Task_TaskRunnerBatch
     * /
    protected $_batch = null;

    /**
     * The database connection to Gems itself
     *
     * @var Zend_Db_Adapter_Abstract
     */
    private $_gemsDb;

    /**
     * The information from the gems__sources for this source
     *
     * @var array
     */
    private $_sourceData;

    /**
     * The database connection to the source, usedable by all implementations that use a database
     *
     * @var Zend_Db_Adapter_Abstract
     */
    private $_sourceDb;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     * Standard constructor for sources
     *
     * @param array $sourceData The information from gems__sources for this source.
     * @param Zend_Db_Adapter_Abstract $gemsDb Do not want to copy db using registry because that is public and this should be private
     */
    public function __construct(array $sourceData, Zend_Db_Adapter_Abstract $gemsDb)
    {
        $this->_sourceData = $sourceData;
        $this->_gemsDb     = $gemsDb;
    }

    /**
     * Returns all surveys for synchronization
     *
     * @return array Pairs gemsId => sourceId
     */
    protected function _getGemsSurveysForSynchronisation()
    {
        $select = $this->_gemsDb->select();
        $select->from('gems__surveys', array('gsu_id_survey', 'gsu_surveyor_id'))
                ->where('gsu_id_source = ?', $this->getId())
                ->order('gsu_surveyor_id');

        return $this->_gemsDb->fetchPairs($select);
    }

    /**
     * Returns all surveys for synchronization
     *
     * @return array of sourceId values or false
     */
    abstract protected function _getSourceSurveysForSynchronisation();

    /**
     * Creates a where filter statement for tokens that do not
     * have a correct name and are in a tokens table
     *
     * @param string $from The tokens that should not occur
     * @param string $fieldName Name of database field to use
     * @return string
     */
    protected function _getTokenFromSqlWhere($from, $fieldName)
    {
        $lsDb = $this->getSourceDatabase();
        $tokField = $lsDb->quoteIdentifier($fieldName);

        foreach (str_split($from) as $check) {
            $checks[] = 'LOCATE(' . $lsDb->quote($check) . ', ' . $tokField . ')';
        }

        return '(' . implode(' OR ', $checks) . ')';
    }

    /**
     * Creates a SQL update statement for tokens that do not
     * have a correct name and are in a tokens table.
     *
     * @param string $from The tokens that should not occur
     * @param string $to The tokens that replace them
     * @param string $fieldName Name of database field to use
     * @return string
     */
    protected function _getTokenFromToSql($from, $to, $fieldName)
    {
        $lsDb = $this->getSourceDatabase();
        if ($from) {
            // Build the sql statement using recursion
            return 'REPLACE(' .
                $this->_getTokenFromToSql(substr($from, 1), substr($to, 1), $fieldName) . ', ' .
                $lsDb->quote($from[0]) . ', ' .
                $lsDb->quote($to[0]) . ')';
        } else {
            return $lsDb->quoteIdentifier($fieldName);
        }
    }

    /**
     * This helper function updates the surveys in the gems_surveys table that
     * no longer exist in in the source and returns a list of their names.
     *
     * @param array $surveyorSids The gsu_surveyor_id's that ARE in the source
     * @param int $userId   Id of the user who takes the action (for logging)
     * @return array The names of the surveys that no longer exist
     */
    protected function _updateGemsSurveyExists(array $surveyorSids, $userId)
    {
        $sqlWhere = 'gsu_id_source = ' . $this->_gemsDb->quote($this->getId()) . '
                AND (gsu_status IS NULL OR gsu_active = 1 OR gsu_surveyor_active = 1)
                AND gsu_surveyor_id NOT IN (' . implode(', ', $surveyorSids) . ')';

        // Fixed values
        $data['gsu_active']          = 0;
        $data['gsu_surveyor_active'] = 0;
        $data['gsu_status']          = 'Survey was removed from source.';
        $data['gsu_changed']         = new MUtil_Db_Expr_CurrentTimestamp();
        $data['gsu_changed_by']      = $userId;

        $this->_gemsDb->update('gems__surveys', $data, $sqlWhere);

        return $this->_gemsDb->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys WHERE ' . $sqlWhere);
    }

    /**
     * Updates this source, both in the database and in memory.
     *
     * @param array $values The values that this source should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    protected function _updateSource(array $values, $userId)
    {
        if ($this->tracker->filterChangesOnly($this->_sourceData, $values)) {


            if (Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_sourceData[$key] . ' => ' . $val . "\n";
                }
                MUtil_Echo::r($echo, 'Updated values for ' . $this->getId());
            }

            if (! isset($values['gso_changed'])) {
                $values['gso_changed'] = new MUtil_Db_Expr_CurrentTimestamp();
            }
            if (! isset($values['gso_changed_by'])) {
                $values['gso_changed_by'] = $userId;
            }

            // Update values in this object
            $this->_sourceData = $values + $this->_sourceData;

            // return 1;
            return $this->_gemsDb->update('gems__sources', $values, array('gso_id_source = ?' => $this->getId()));

        } else {
            return 0;
        }
    }

    /**
     * Adds database (if needed) and tablename prefix to the table name
     *
     * @param return $tableName
     * @param boolean $addDatabaseName Optional, when true (= default) and there is a database name then it is prepended to the name.
     * @return string
     */
    protected function addDatabasePrefix($tableName, $addDatabaseName = true)
    {
        return ($addDatabaseName && $this->_sourceData['gso_ls_database'] ? $this->_sourceData['gso_ls_database'] . '.' : '') .
            $this->_sourceData['gso_ls_table_prefix'] .
            $tableName;
    }

    /**
     * Extract limit and offset from the filter and add it to a select
     *
     * @param array $filter
     * @param Zend_Db_Select $select
     */
    protected function filterLimitOffset(&$filter, $select)
    {
        $limit = null;
        $offset = null;

        if (array_key_exists('limit', $filter)) {
            $limit = (int) $filter['limit'];
            unset($filter['limit']);
        }
        if (array_key_exists('offset', $filter)) {
            $offset = (int) $filter['offset'];
            unset($filter['offset']);
        }
        $select->limit($limit, $offset);
    }

    /**
     *
     * @return string Base url for source
     */
    protected function getBaseUrl()
    {
        return $this->_sourceData['gso_ls_url'];
    }

    /**
     *
     * @return int The source Id of this source
     */
    public function getId()
    {
        return $this->_sourceData['gso_id_source'];
    }

    /**
     * Returns the recordcount for a given filter
     *
     * Abstract implementation is not efficient, sources should handle this as efficient
     * as possible.
     *
     * @param array $filter filter array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int
     */
    public function getRawTokenAnswerRowsCount(array $filter, $surveyId, $sourceSurveyId = null)
    {
        $answers = $this->getRawTokenAnswerRows($filter, $surveyId, $sourceSurveyId);
        return count($answers);
    }

    /**
     * Get the db adapter for this source
     *
     * @return Zend_Db_Adapter_Abstract
     */
    protected function getSourceDatabase()
    {
        if (! $this->_sourceDb) {

            if ($dbConfig['dbname'] = $this->_sourceData['gso_ls_database']) {

                // Default config values from gemsDb
                $gemsConfig = $this->_gemsDb->getConfig();
                $gemsName   = $gemsConfig['dbname'];

                if (($dbConfig['dbname'] != $gemsName) &&
                    ($adapter = $this->_sourceData['gso_ls_adapter'])) {

                    //If upgrade has run and we have a 'charset' use it
                    if (array_key_exists('gso_ls_charset', $this->_sourceData)) {
                        $dbConfig['charset']  = $this->_sourceData['gso_ls_charset'] ? $this->_sourceData['gso_ls_charset'] : $gemsConfig['charset'];
                    }
                    $dbConfig['host']     = $this->_sourceData['gso_ls_dbhost'] ? $this->_sourceData['gso_ls_dbhost'] : $gemsConfig['host'];
                    $dbConfig['username'] = $this->_sourceData['gso_ls_username'] ? $this->_sourceData['gso_ls_username'] : $gemsConfig['username'];
                    $dbConfig['password'] = $this->_sourceData['gso_ls_password'] ? $this->_sourceData['gso_ls_password'] : $gemsConfig['password'];

                    $this->_sourceDb = Zend_Db::factory($adapter, $dbConfig);
                }
            }

            // In all other cases use $_gemsDb
            if (! $this->_sourceDb) {
                $this->_sourceDb = $this->_gemsDb;
            }
        }

        return $this->_sourceDb;
    }

    /**
     * Returns all info from the Gems surveys table for a givens Gems Survey Id
     *
     * Uses internal caching to prevent multiple db lookups during a program run (so no caching
     * beyond page generation time)
     *
     * @param int $surveyId
     * @param string $field Optional field to retrieve data for
     * @return array
     */
    protected function getSurveyData($surveyId, $field = null) {
        static $cache = array();

        if (! isset($cache[$surveyId])) {
            $cache[$surveyId] = $this->_gemsDb->fetchRow('SELECT * FROM gems__surveys WHERE gsu_id_survey = ? LIMIT 1', $surveyId, Zend_Db::FETCH_ASSOC);
        }

        if (null === $field) {
            return $cache[$surveyId];
        } else {
            if (isset($cache[$surveyId][$field])) {
                return $cache[$surveyId][$field];
            }
        }
    }

    /**
     * Returns true if a batch is set
     *
     * @return boolean
     * /
    public function hasBatch()
    {
        return ($this->_batch instanceof Gems_Task_TaskRunnerBatch);
    }

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param Gems_Task_TaskRunnerBatch $batch
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     */
    public function synchronizeSurveyBatch(Gems_Task_TaskRunnerBatch $batch, $userId)
    {
        // Surveys in Gems
        $select = $this->_gemsDb->select();
        $select->from('gems__surveys', array('gsu_id_survey', 'gsu_surveyor_id'))
                ->where('gsu_id_source = ?', $this->getId())
                ->order('gsu_surveyor_id');

        $gemsSurveys = $this->_gemsDb->fetchPairs($select);
        if (!$gemsSurveys) {
            $gemsSurveys = array();
        }

        // Surveys in Source
        $sourceSurveys = $this->_getSourceSurveysForSynchronisation();
        if ($sourceSurveys) {
            $sourceSurveys = array_combine($sourceSurveys, $sourceSurveys);
        } else {
            $sourceSurveys = array();
        }

        // Always those already in the database
        foreach ($gemsSurveys as $surveyId => $sourceSurveyId) {

            if (isset($sourceSurveys[$sourceSurveyId])) {
                $batch->addTask('Tracker_CheckSurvey', $this->getId(), $sourceSurveyId, $surveyId, $userId);
                $batch->addTask('Tracker_AddRefreshQuestions', $this->getId(), $sourceSurveyId, $surveyId);
            } else {
                // Do not pass the source id when it no longer exists
                $batch->addTask('Tracker_CheckSurvey', $this->getId(), null, $surveyId, $userId);
            }
        }

        // Now add the new ones
        foreach (array_diff($sourceSurveys, $gemsSurveys) as $sourceSurveyId) {
            $batch->addTask('Tracker_CheckSurvey', $this->getId(), $sourceSurveyId, null, $userId);
            $batch->addTask('Tracker_AddRefreshQuestions', $sourceSurveyId, null, $userId);
        }
    }

    /**
     * Updates the gems__tokens table so all tokens stick to the (possibly) new token name rules.
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return int The number of tokens changed
     */
    protected function updateTokens($userId, $updateTokens = true)
    {
        $tokenLib = $this->tracker->getTokenLibrary();

        $sql = 'UPDATE gems__tokens
                    SET gto_id_token = ' . $this->_getTokenFromToSql($tokenLib->getFrom(), $tokenLib->getTo(), 'gto_id_token') . ',
                        gto_changed = CURRENT_TIMESTAMP,
                        gto_changed_by = ' . $this->_gemsDb->quote($userId) . '
                    WHERE ' . $this->_getTokenFromSqlWhere($tokenLib->getFrom(), 'gto_id_token') . ' AND
                        gto_id_survey IN (SELECT gsu_id_survey FROM gems__surveys WHERE gsu_id_source = ' . $this->_gemsDb->quote($this->getId()) . ')';

        return $this->_gemsDb->query($sql)->rowCount();
    }

    /**
     * Set the batch to be used by this source
     *
     * Use $this->hasBatch to check for existence
     *
     * @param Gems_Task_TaskRunnerBatch $batch
     * /
    public function setBatch(Gems_Task_TaskRunnerBatch $batch)
    {
        $this->_batch = $batch;
    } // */
}
