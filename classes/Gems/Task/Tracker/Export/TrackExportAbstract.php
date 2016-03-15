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
 * @version    $Id: TrackExportAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Export;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Field\FieldInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 14, 2016 2:17:09 PM
 */
abstract class TrackExportAbstract extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var file resource
     */
    private $_file;

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
     * Write the array keys to the output
     *
     * @param array $data With headers as the keys
     */
    protected function exportFieldHeaders(array $data)
    {
        fwrite($this->_file, implode("\t", array_keys($data)) . "\r\n");
    }

    /**
     * Write the array to the output to file
     *
     * @param array $data
     */
    protected function exportFieldData(array $data)
    {
        $replacements = array("\n" => '\\n', "\r" => '\\r', "\t" => '\\t');

        foreach ($data as &$item) {
            $item = strtr((string) $item, $replacements);
        }
        fwrite($this->_file, implode("\t", $data) . "\r\n");
    }

    /**
     * Flush the output
     */
    protected function exportFlush()
    {
        fflush($this->_file);
    }

    /**
     * Write the export type to the output
     *
     * @param array $data With headers as the keys
     */
    protected function exportTypeHeader($header, $prependNewline = true)
    {
        if ($prependNewline) {
            fwrite($this->_file, "\r\n");
        }
        fwrite($this->_file, "$header\r\n");
    }

    /**
     * Sets the batch this task belongs to
     *
     * This method will be called from the \Gems_Task_TaskRunnerBatch upon execution of the
     * task. It allows the task to communicate with the batch queue.
     *
     * @param \MUtil_Task_TaskBatch $batch
     * @return \MUtil_Task_TaskInterface (continuation pattern)
     */
    public function setBatch(\MUtil_Task_TaskBatch $batch)
    {
        parent::setBatch($batch);

        $this->_file = $batch->getVariable('file');

        return $this;
    }

    /**
     * Translate a field code to the field order number
     *
     * @param FieldsDefinition $fields
     * @param string $fieldId
     * @return string {order} or original value
     */
    protected function translateFieldCode(FieldsDefinition $fields, $fieldId)
    {
        $field = $fields->getField($fieldId);
        if ($field instanceof FieldInterface) {
            return  '{f' . $field->getOrder() . '}';
        }

        return $fieldId;
    }
}
