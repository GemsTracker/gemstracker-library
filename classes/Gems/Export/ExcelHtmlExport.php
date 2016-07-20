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

namespace Gems\Export;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class ExcelHtmlExport extends ExportAbstract
{
    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.xls';

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'ExcelHtmlExport';
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
        $element->setLabel($this->_('Excel options'))
                ->setMultiOptions(array(
                    'formatVariable' => $this->_('Export labels instead of field names'),
                    'formatAnswer' => $this->_('Format answers')
                ))
                ->setBelongsTo($this->getName())
                ->setSeparator('');
        $elements['format'] = $element;

        return $elements;
    }

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues()
    {
        return array('format'=>array('formatVariable', 'formatAnswer'));
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    protected function addheader($filename)
    {
        $file = fopen($filename, 'w');
        fwrite($file, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv=Content-Type content="text/html; charset=UTF-8">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 11">
<style>
    /* Default styles for tables */

    table {
        border-collapse: collapse;
        border: .5pt solid #000000;
    }

    tr th {
        font-weight: bold;
        padding: 3px 8px;
        border: .5pt solid #000000;
        background: #c0c0c0;
    }
    tr td {
        padding: 3px 8px;
        border: .5pt solid #000000;
    }
    td {
        mso-number-format:"\@";
    }
    td.number {
        mso-number-format:"\#\,\#\#0\.##############";
    }
    td.date {
        mso-number-format:"yyyy\-mm\-dd";
    }
    td.datetime {
        mso-number-format:"dd\-mm\-yyyy hh\:mm\:ss";
    }
    td.time {
        mso-number-format:"hh\:mm\:ss";
    }
</style>
</head>
<body>');

        //Only for the first row: output headers
        $labeledCols = $this->getLabeledColumns();
        $output = "<table>\r\n";
        $output .= "\t<thead>\r\n";
        $output .= "\t\t<tr>\r\n";

        if (isset($this->data[$this->getName()]) && isset($this->data[$this->getName()]['format']) && in_array('formatVariable', $this->data[$this->getName()]['format'])) {
            foreach ($labeledCols as $columnName) {
                if ($label = $this->model->get($columnName, 'label')) {

                    $output .= "\t\t\t<th>" . $label. "</th>\r\n";
                }
            }
        } else {
            foreach ($labeledCols as $columnName) {
                if ($label = $this->model->get($columnName, 'label')) {

                    $output .= "\t\t\t<th>" . $columnName. "</th>\r\n";
                }
            }
        }
        $output .= "\t\t</tr>\r\n";
        $output .= "\t</thead>\r\n";
        $output .= "\t<tbody>\r\n";

        fwrite($file, $output);
        fclose($file);
    }

    /**
     * Add model rows to file. Can be batched
     * @param array $data                       Data submitted by export form
     * @param array $modelId                    Model Id when multiple models are passed
     * @param string $tempFilename              The temporary filename while the file is being written
     * @param array  $filter                    Filter (limit) to use
     */
    public function addRows($data, $modelId, $tempFilename, $filter)
    {
        $name = $this->getName();
        if (!(isset($data[$name]) && isset($data[$name]['format']) && in_array('formatAnswer', $data[$name]['format']))) {
            $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
        }
        parent::addRows($data, $modelId, $tempFilename, $filter);
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    public function addRow($row, $file)
    {
        fwrite($file, "\t\t<tr>\r\n");
        $exportRow = $this->filterRow($row);
        $labeledCols = $this->getLabeledColumns();
        foreach($labeledCols as $columnName) {
            // We could be missing data for a column, just skip it
            $result = null;
            if (array_key_exists($columnName, $exportRow)) {
                $result = $exportRow[$columnName];
            }            
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $output = '<td class="date">'.$result.'</td>';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $output = '<td class="datetime">'.$result.'</td>';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $output = '<td class="time">'.$result.'</td>';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    if (isset($options['multiOptions']) && (is_numeric(array_shift($options['multiOptions'])))) {
                        $output = '<td>'.$result.'</td>';
                    } else {
                        $output = '<td class="number">'.$result.'</td>';
                    }
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:
                    $output = '<td>'.$result.'</td>';
                    break;
            }
            fwrite($file, $output);
        }
        fwrite($file, "\t\t</tr>\r\n");
    }

    /**
     * Add a footer to a specific file
     * @param string $filename The temporary filename while the file is being written
     */
    public function addFooter($filename)
    {
        $file = fopen($filename, 'a');
        fwrite($file, '            </tbody>
        </table>
    </body>
</html>');
        fclose($file);
    }
}