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
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export_CsvExport extends \Gems_Export_ExportAbstract
{
    protected $delimiter = ';';

    protected $fileExtension = '.csv';

    /**
     * return name of the specific export
     */
    public function getName() {
        return 'CsvExport';
    }

    /**
     * form elements for extra options for this particular export option
     */
    public function getFormElements(&$form, &$data)
    {
        $element = $form->createElement('multiCheckbox', 'format');
        $element->setLabel($this->_('CSV options'))
            ->setMultiOptions(array(
                'addHeader' => $this->_('Add headers with column names'),
                'formatVariable' => $this->_('Export questions instead of variable names'),
                'formatAnswer' => $this->_('Format answers')
            ));
        $elements[] = $element;

        $element = $form->createElement('select', 'delimiter');
        $element->setLabel($this->_('Delimiter'))
            ->setMultiOptions(array(',' => ',', ';' => ';'));
        $elements[] = $element;

        return $elements;
    }

    /**
     * Sets the default form values when this export type is first chosen
     *
     * @return array
     */
    public function getDefaultFormValues()
    {
        return array('format'=>array('addHeader', 'formatVariable', 'formatAnswer'), 'delimiter' => ';');
    }

    /**
     * Add headers to a specific file
     */
    protected function addheader($filename)
    {
        $file = fopen($filename, 'w');
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        fwrite($file, $bom);

        $name = $this->getName();
        if (isset($this->data[$name]) && isset($this->data[$name]['format']) && in_array('addHeader', $this->data[$name]['format'])) {
            $labeledCols = $this->model->getColNames('label');
            $labels = array();

            if (in_array('formatVariable', $this->data[$name]['format'])) {
                foreach($labeledCols as $columnName) {
                    $labels[] = $this->model->get($columnName, 'label');
                }
            } else {
                $labels = $labeledCols;
            }

            if (isset($this->data[$name]) && isset($this->data[$name]['delimiter'])) {
                $this->delimiter = $this->data[$name]['delimiter'];
            }

            fputcsv($file, $labels, $this->delimiter, "'");
        }

        fclose($file);
    }

    public function addRows($exportModelSourceName, $filter, $data, $tempFilename)
    {
        $name = $this->getName();
        if (isset($data[$name]) && isset($data[$name]['delimiter'])) {
            $this->delimiter = $data[$name]['delimiter'];
        }
        if (!(isset($data[$name]) && isset($data[$name]['format']) && in_array('formatAnswer', $data[$name]['format']))) {
            $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
        }
        parent::addRows($exportModelSourceName, $filter, $data, $tempFilename);
    }

    public function addRow($row, $file)
    {
        $exportRow = $this->filterRow($row);
        $labeledCols = $this->model->getColNames('label');
        $exportRow = array_replace(array_flip($labeledCols), $exportRow);
        fputcsv($file, $exportRow, $this->delimiter, "'");
    }

    /**
     * Formatting of strings for SPSS export. Enclose in single quotes and escape single quotes
     * with a single quote
     *
     * Example:
     * This isn't hard to understand
     * ==>
     * 'This isn''t hard to understand'
     *
     * @param type $input
     * @return string
     */
    public function formatString($input)
    {
        $output = strip_tags($input);
        $output = str_replace(array("'", "\r", "\n"), array("''", ' ', ' '), $output);
        //$output = "'" . $output . "'";
        return $output;
    }

    protected function preprocessModel()
    {
        $labeledCols = $this->model->getColNames('label');
        foreach($labeledCols as $columnName) {
            $options = array();
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $options['storageFormat'] = 'yyyy-MM-dd';
                    $options['dateFormat']    = 'yyyy-MM-dd';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $options['dateFormat']    = 'dd-MM-yyyy HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $options['dateFormat']    = 'HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:
                    $type                      = \MUtil_Model::TYPE_STRING;
                    $options['formatFunction'] = 'formatString';
                    break;
            }
            $options['type']           = $type;
            $this->model->set($columnName, $options);
        }
    }

    /**
     * Set the batch to be used by this source
     *
     * Use $this->hasBatch to check for existence
     *
     * @param \Gems_Task_TaskRunnerBatch $batch
     */
    public function setBatch(\Gems_Task_TaskRunnerBatch $batch)
    {
        $this->batch = $batch;
    }
}