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
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Export_CsvExport extends \Gems_Export_ExportAbstract
{
    /**
     * Delimiter used for CSV export
     * @var string
     */
    protected $delimiter = ';';

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.csv';

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'CsvExport';
    }

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil_Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(&$form, &$data)
    {
        $element = $form->createElement('multiCheckbox', 'format');
        $element->setLabel($this->_('CSV options'))
                ->setMultiOptions(array(
                    'addHeader' => $this->_('Add headers with column names'),
                    'formatVariable' => $this->_('Export labels instead of field names'),
                    'formatAnswer' => $this->_('Format answers')
                ))
                ->setBelongsTo($this->getName());
        $elements['format'] = $element;

        $element = $form->createElement('select', 'delimiter');
        $element->setLabel($this->_('Delimiter'))
                ->setMultiOptions(array(',' => ',', ';' => ';'))
                ->setBelongsTo($this->getName());
        $elements['delimiter'] = $element;

        return $elements;
    }

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues()
    {
        return array('format'=>array('addHeader', 'formatVariable', 'formatAnswer'), 'delimiter' => ';');
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    protected function addHeader($filename)
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

    /**
     * Add model rows to file. Can be batched
     * @param array $data                       Data submitted by export form
     * @param array $modelId                    Model Id when multiple models are passed
     * @param string $tempFilename              The temporary filename while the file is being written
     */
    public function addRows($data, $modelId, $tempFilename)
    {
        $name = $this->getName();
        if (isset($data[$name]) && isset($data[$name]['delimiter'])) {
            $this->delimiter = $data[$name]['delimiter'];
        }
        if (!(isset($data[$name]) && isset($data[$name]['format']) && in_array('formatAnswer', $data[$name]['format']))) {
            $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
        }
        parent::addRows($data, $modelId, $tempFilename);
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
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

    /**
     * Preprocess the model to add specific options
     */
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
}