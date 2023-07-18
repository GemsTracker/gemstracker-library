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

use Gems\Db\Dsn;
use Laminas\Db\Sql\Sql;
use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Select;
use Laminas\Db\Adapter\Adapter;
use Gems\Encryption\ValueEncryptor;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;

/**
 * Abstract implementation of SourceInterface containing basic utilities and logical
 * separation between the \Gems database and the Source database
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class SourceAbstract extends \MUtil\Translate\TranslateableAbstract
    implements \Gems\Tracker\Source\SourceInterface
{

    /**
     * @var array meta data fields that are included in a survey table
     */
    public static $metaFields = [];

    /**
     * The database connection to the source, usedable by all implementations that use a database
     */
    private Adapter $_sourceDb;

    /**
     * ResultFetcher interface to the source database.
     */
    private ResultFetcher $_sourceResultFetcher;

    /**
     * ResultFetcher interface to the Gems database.
     */
    private ResultFetcher $_gemsResultFetcher;

    /**
     *
     * @var \Gems\Tracker
     */
    protected $tracker;

    /**
     * @var ValueEncryptor
     */
    protected $valueEncryptor;

    /**
     * Standard constructor for sources.
     * We do not want to copy db using registry because that is public and
     * this should be private.
     *
     * @param array $_sourceData The information from gems__sources for this source.
     * @param Adapter $_gemsDb   The database connection to \Gems itself
     */
    public function __construct(
        private array $_sourceData,
        private Adapter $_gemsDb
    ) {
        $this->_gemsResultFetcher = new ResultFetcher($this->_gemsDb);
    }

    /**
     * Returns all surveys for synchronization
     *
     * @return array Pairs gemsId => sourceId
     */
    protected function _getGemsSurveysForSynchronisation()
    {
        $select = $this->_gemsResultFetcher->getSelect();
        $select->from('gems__surveys')
            ->columns(['gsu_id_survey', 'gsu_surveyor_id'])
            ->where(['gsu_id_source' => $this->getId()])
            ->order('gsu_surveyor_id');

        return $this->_gemsResultFetcher->fetchPairs($select);
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
        $lsPlatform = $this->getSourceDatabase()->getPlatform();
        $tokField = $lsPlatform->quoteIdentifier($fieldName);

        foreach (str_split($from) as $check) {
            $checks[] = 'LOCATE(' . $lsPlatform->quoteValue($check) . ', ' . $tokField . ')';
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
        $lsPlatform = $this->getSourceDatabase()->getPlatform();
        if ($from) {
            // Build the sql statement using recursion
            return 'REPLACE(' .
                $this->_getTokenFromToSql(substr($from, 1), substr($to, 1), $fieldName) . ', ' .
                $lsPlatform->quoteValue($from[0]) . ', ' .
                $lsPlatform->quoteValue($to[0]) . ')';
        } else {
            return $lsPlatform->quoteIdentifier($fieldName);
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
        $platform = $this->_gemsDb->getPlatform();
        $sqlWhere = 'gsu_id_source = ' . $platform->quoteValue(strval($this->getId())) . '
                AND (gsu_status IS NULL OR gsu_active = 1 OR gsu_surveyor_active = 1)
                AND gsu_surveyor_id NOT IN (' . implode(', ', $surveyorSids) . ')';

        // Fixed values
        $data['gsu_active']          = 0;
        $data['gsu_surveyor_active'] = 0;
        $data['gsu_status']          = 'Survey was removed from source.';
        $data['gsu_changed']         = new \MUtil\Db\Expr\CurrentTimestamp();
        $data['gsu_changed_by']      = $userId;

        $sql = new Sql($this->_gemsDb);
        $update = $sql->update('gems__surveys')->set($data)->where($sqlWhere);
        $sql->prepareStatementForSqlObject($update)->execute();

        return $this->_gemsResultFetcher->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys WHERE ' . $sqlWhere);
    }

    /**
     * Updates this source, both in the database and in memory.
     *
     * @param array $values The values that this source should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    protected function _updateSource(array $values, $userId): int
    {
        if (! $this->tracker->filterChangesOnly($this->_sourceData, $values)) {
            return 0;
        }

        if (\Gems\Tracker::$verbose) {
            $echo = '';
            foreach ($values as $key => $val) {
                $echo .= $key . ': ' . $this->_sourceData[$key] . ' => ' . $val . "\n";
            }
            \MUtil\EchoOut\EchoOut::r($echo, 'Updated values for ' . $this->getId());
        }

        if (! isset($values['gso_changed'])) {
            $values['gso_changed'] = new \MUtil\Db\Expr\CurrentTimestamp();
        }
        if (! isset($values['gso_changed_by'])) {
            $values['gso_changed_by'] = $userId;
        }

        // Update values in this object
        $this->_sourceData = $values + $this->_sourceData;

        // return 1;
        $sql = new Sql($this->_gemsDb);
        $update = $sql->update('gems__sources')->set($values)->where(['gso_id_source' => $this->getId()]);
        $sql->prepareStatementForSqlObject($update)->execute();

        return 1;
    }

    /**
     * Adds database (if needed) and tablename prefix to the table name
     *
     * @param string $tableName
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
     * @return boolean When true can export when survey inactive in source
     */
    public function canExportInactive()
    {
        return false;
    }

    /**
     * Extract limit and offset from the filter and add it to a select
     *
     * @param array $filter
     * @param Select $select
     */
    protected function filterLimitOffset(array &$filter, Select $select): void
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
        $select->limit($limit)->offset($offset);
    }

    /**
     * Returns all the gemstracker names for attributes stored in source for a token
     *
     * @return array
     */
    public function getAttributes() {
        return array();
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
    public function getId(): int
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
     * @param int $surveyId \Gems Survey Id
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
     */
    public function getSourceDatabase(): Adapter
    {
        if (! isset($this->_sourceDb)) {

            if ($dbConfig['dbname'] = $this->_sourceData['gso_ls_database']) {

                // Default config values from gemsDb
                /** @var AbstractConnection */
                $connection = $this->_gemsDb->driver->getConnection();
                $gemsConfig = $connection->getConnectionParameters();
                $gemsName   = $gemsConfig['database'];

                if (($dbConfig['dbname'] != $gemsName) &&
                    ($driver = $this->_sourceData['gso_ls_adapter'])) {
                    $dbConfig['driver'] = $driver;

                    //If upgrade has run and we have a 'charset' use it
                    if (array_key_exists('gso_ls_charset', $this->_sourceData)) {
                        $dbConfig['charset'] = $this->_sourceData['gso_ls_charset']
                            ? $this->_sourceData['gso_ls_charset']
                            : $gemsConfig['charset'];
                    }
                    $dbConfig['host']     = $this->_sourceData['gso_ls_dbhost']
                        ? $this->_sourceData['gso_ls_dbhost']
                        : $gemsConfig['host'];
                    if (isset($this->_sourceData['gso_ls_dbport'])) {
                        $dbConfig['port'] = $this->_sourceData['gso_ls_dbport'];
                    } elseif (isset($gemsConfig['port'])) {
                        $dbConfig['port'] = $gemsConfig['port'];
                    }
                    $dbConfig['username'] = $this->_sourceData['gso_ls_username']
                        ? $this->_sourceData['gso_ls_username']
                        : $gemsConfig['username'];
                    $dbConfig['password'] = $this->_sourceData['gso_ls_password']
                        ? $this->valueEncryptor->decrypt($this->_sourceData['gso_ls_password'])
                        : $gemsConfig['password'];

                    $this->_sourceDb = new Adapter(new Pdo(new \Pdo(Dsn::fromConfig($dbConfig))));
                }
            }

            // In all other cases use $_gemsDb
            // FIXME _gemsDb is still \Zend_Db_Adapter_Abstract
            if (! isset($this->_sourceDb)) {
                $this->_sourceDb = $this->_gemsDb;
            }
        }

        return $this->_sourceDb;
    }

    /**
     * Get the result fetcher for the source database.
     */
    public function getSourceResultFetcher(): ResultFetcher
    {
        if (isset($this->_sourceResultFetcher)) {
            return $this->_sourceResultFetcher;
        }
        if (! isset($this->_sourceDb)) {
            $this->getSourceDatabase();
        }

        $this->_sourceResultFetcher = new ResultFetcher($this->_sourceDb);

        return $this->_sourceResultFetcher;
    }

    /**
     * Returns all info from the \Gems surveys table for a givens \Gems Survey Id
     *
     * Uses internal caching to prevent multiple db lookups during a program run (so no caching
     * beyond page generation time)
     *
     * @param int $surveyId
     * @param string $field Optional field to retrieve data for
     * @return array
     */
    protected function getSurveyData($surveyId, $field = null)
    {
        static $cache = array();

        if (! isset($cache[$surveyId])) {
            $cache[$surveyId] = $this->_gemsResultFetcher->fetchRow(
                'SELECT * FROM gems__surveys WHERE gsu_id_survey = ? LIMIT 1',
                [$surveyId]
            );
        }

        if (null === $field) {
            return $cache[$surveyId];
        } else {
            if (isset($cache[$surveyId][$field])) {
                return $cache[$surveyId][$field];
            }
        }

        return [];
    }

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param \Gems\Task\TaskRunnerBatch $batch
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     */
    public function synchronizeSurveyBatch(\Gems\Task\TaskRunnerBatch $batch, $userId)
    {
        // Surveys in \Gems
        $select = $this->_gemsResultFetcher->getSelect();
        $select->from('gems__surveys')
            ->columns(['gsu_id_survey', 'gsu_surveyor_id'])
            ->where(['gsu_id_source' => $this->getId()])
            ->order('gsu_surveyor_id');

        $gemsSurveys = $this->_gemsResultFetcher->fetchPairs($select);
        if (!$gemsSurveys) {
            $gemsSurveys = [];
        }

        // Surveys in Source
        $sourceSurveys = $this->_getSourceSurveysForSynchronisation();
        if ($sourceSurveys) {
            $sourceSurveys = array_combine($sourceSurveys, $sourceSurveys);
        } else {
            $sourceSurveys = [];
        }

        // Always those already in the database
        foreach ($gemsSurveys as $surveyId => $sourceSurveyId) {

            if (isset($sourceSurveys[$sourceSurveyId])) {
                $batch->addTask('Tracker\\CheckSurvey', $this->getId(), $sourceSurveyId, $surveyId, $userId);
                $batch->addTask('Tracker\\AddRefreshQuestions', $this->getId(), $sourceSurveyId, $surveyId);
            } else {
                // Do not pass the source id when it no longer exists
                $batch->addTask('Tracker\\CheckSurvey', $this->getId(), null, $surveyId, $userId);
            }
        }

        // Now add the new ones
        foreach (array_diff($sourceSurveys, $gemsSurveys) as $sourceSurveyId) {
            $batch->addTask('Tracker\\CheckSurvey', $this->getId(), $sourceSurveyId, null, $userId);
            $batch->addTask('Tracker\\AddRefreshQuestions', $this->getId(), $sourceSurveyId, null);
        }

        return [];
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
        $sourceId = $this->_gemsDb->getPlatform()->quoteValue($this->getId());

        $sql = new Sql($this->_gemsDb);
        $update = $sql->update('gems__tokens')
                ->set([
                    'gto_id_token' => $this->_getTokenFromToSql($tokenLib->getFrom(), $tokenLib->getTo(), 'gto_id_token'),
                    'gto_changed' => new \Laminas\Db\Sql\Expression('CURRENT_TIMESTAMP'),
                    'gto_changed_by' => $userId,
                ])
                ->where(
                    $this->_getTokenFromSqlWhere($tokenLib->getFrom(), 'gto_id_token')
                    . ' AND gto_id_survey IN (SELECT gsu_id_survey FROM gems__surveys WHERE gsu_id_source = ' . $sourceId . ')'
                );
        $result = $sql->prepareStatementForSqlObject($update)->execute();

        return $result->getAffectedRows();
    }
}
