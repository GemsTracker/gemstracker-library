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
