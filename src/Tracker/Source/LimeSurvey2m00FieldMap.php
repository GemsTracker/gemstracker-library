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
class LimeSurvey2m00FieldMap extends \Gems\Tracker\Source\LimeSurvey1m9FieldMap
{
    /**
     * Get the table structure of the token table
     *
     * @return array List of \Zend_DB Table metadata
     */
    public function getTokenTableStructure()
    {
        $tableName = $this->_getTokenTableName();

        $table = new \Zend_Db_Table(['name' => $tableName, 'db' => $this->lsDb]);
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
