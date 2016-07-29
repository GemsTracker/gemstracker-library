<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackSurveyExportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Export;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 18, 2016 7:59:10 PM
 */
class TrackSurveyExportTask extends TrackExportAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($trackId = null, $surveyId = null)
    {
        $batch = $this->getBatch();
        $select = $this->db->select();

        $select->from('gems__surveys', array('gsu_export_code', 'gsu_survey_name', 'gsu_survey_description', 'gsu_surveyor_id'))
                ->where('gsu_id_survey = ?', $surveyId);
        // \MUtil_Echo::track($select->__toString(), $roundId);

        $data = $this->db->fetchRow($select);
        // \MUtil_Echo::track($data);

        if ($data) {
            $count = $batch->addToCounter('surveys_exported');

            if ($count == 1) {
                $this->exportTypeHeader('surveys');
                $this->exportFieldHeaders($data);
            }
            $this->exportFieldData($data);
            $this->exportFlush();

            $batch->setMessage('survey_export', sprintf(
                    $this->plural('%d survey code exported', '%d survey codes exported', $count),
                    $count
                    ));
        }
    }
}
