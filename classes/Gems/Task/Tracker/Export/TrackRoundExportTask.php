<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackRoundExportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Export;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 12, 2016 5:31:00 PM
 */
class TrackRoundExportTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($roundId = null)
    {
        $batch = $this->getBatch();
        $select = $this->db->select();

        $select->from('gems__rounds', array(
            'gro_id_order', 'gro_id_relationfield', 'gro_round_description', 'gro_icon_file',
            'gro_changed_event', 'gro_display_event',
            'gro_valid_after_source', 'gro_valid_after_field', 'gro_valid_after_unit', 'gro_valid_after_length',
            'gro_valid_for_source', 'gro_valid_for_field', 'gro_valid_for_unit', 'gro_valid_for_length',
            'gro_organizations', 'gro_code',
            ))  ->joinInner('gems__surveys', 'gro_id_survey = gsu_id_survey', array(
                'survey_export_code' => 'gsu_export_code',
                )) // gro_id_survey
                ->joinLeft('gems__rounds AS va', 'gems__rounds.gro_valid_after_id = va.gro_id_round', array(
                    'valid_after' => 'va.gro_id_order',
                    )) // gro_valid_after_id
                ->joinLeft('gems__rounds AS vf', 'gems__rounds.gro_valid_for_id = vf.gro_id_round', array(
                     'valid_for' => 'vf.gro_id_order',
                    )) // gro_valid_for_id
                ->where('gems__rounds.gro_id_round = ?', $roundId);
        \MUtil_Echo::track($select->__toString(), $roundId);
        $data = $this->db->fetchRow($select);
        \MUtil_Echo::track($data);
        if ($data) {
            $count = $batch->addToCounter('rounds_exported');
            $file  = $batch->getVariable('file');

            if ($count == 1) {
                // Round data
                fwrite($file, "\r\n");
                fwrite($file, "rounds\r\n");
                fwrite($file, implode("\t", array_keys($data)) . "\r\n");
            }
            fwrite($file, implode("\t", $data) . "\r\n");

            $batch->setMessage('rounds_export', sprintf(
                    $this->plural('%d round exported', '%d rounds exported', $count),
                    $count
                    ));

            fflush($file);
        }
    }
}
