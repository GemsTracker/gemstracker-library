<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LimeSurvey1m9FieldMap.php 2041 2014-07-23 16:17:51Z matijsdejong $
 */

/**
 * A fieldmap object adds LS source code knowledge and interpretation to the database data
 * about a survey. This enables the code to work with the survey object.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Source_LimeSurvey2m00FieldMap extends \Gems_Tracker_Source_LimeSurvey1m9FieldMap
{

    /**
     * Get the survey table structure (meta data)
     *
     * @return array Table meta data
     */
    public function getSurveyTableStructure()
    {
        $metaData = $this->loadTableMetaData();

        return $metaData;
    }

    /**
     * There exists a survey table for each active survey. The table contains the answers to the survey
     *
     * @return string Name of survey table for this survey
     */
    protected function _getTokenTableName()
    {
        return $this->tablePrefix . \Gems_Tracker_Source_LimeSurvey1m9Database::TOKEN_TABLE . $this->sourceSurveyId;
    }

    /**
     * Get the table structure of the token table
     *
     * @return array List of Zend_DB Table metadata
     */
    public function getTokenTableStructure()
    {
        $tableName = $this->_getTokenTableName();

        $table = new \Zend_DB_Table(array('name' => $tableName, 'db' => $this->lsDb));
        $info = $table->info();
        $metaData = $info['metadata'];

        return $metaData;
    }

    /**
     * Execute a Database query on the limesurvey Database
     *
     * @param $sql mixed SQL query to perform on the limesurvey database
     * @param array $bindValues optional bind values for the Query
     */
    public function lsDbQuery($sql, $bindValues=array())
    {
        $this->lsDb->query($sql, $bindValues);
    }
}
