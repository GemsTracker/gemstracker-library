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

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel_Shared_Date;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class ExcelExport extends ExportAbstract
{
    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.xlsx';

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'ExcelExport';
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
                ->setBelongsTo($this->getName());
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
        $excelObject = PHPExcel_IOFactory::load($filename);
        //$excelObject = new PHPExcel();
        $excelObject->getProperties()
            ->setCreator("Gemstracker")
            ->setLastModifiedBy("Gemstracker")
            ->setTitle($this->model->getName());

        $activeSheet = $excelObject->getActiveSheet();

        $columnHeaders = $this->getColumnHeaders();
        $row = 1;

        $exportName = $this->getName();

        $i=0;
        foreach($columnHeaders as $columnName=>$columnHeader) {
            $column = $this->getColumn($i);
            $cell = $column . $row;
            if (isset($this->data[$exportName]) && isset($this->data[$exportName]['format']) && in_array('formatVariable', $this->data[$exportName]['format'])) {
                $activeSheet->setCellValue($cell, $columnHeader);
            } else {
                $activeSheet->setCellValue($cell, $columnName);
            }
            if ($excelCellSize = $this->model->get($columnName, 'excelCellSize')) {
                $activeSheet->getColumnDimension($column)->setWidth($excelCellSize);
            } else {
                $activeSheet->getColumnDimension($column)->setAutoSize(true);
            }
            $i++;
        }

        $activeSheet->getStyle("A1:$cell")->getFont()->setBold(true);

        $objWriter = PHPExcel_IOFactory::createWriter($excelObject, "Excel2007");
        $objWriter->save($filename);
    }

    /**
     * Add model rows to file. Can be batched
     * @param array $data                       Data submitted by export form
     * @param array $modelId                    Model Id when multiple models are passed
     * @param string $tempFilename              The temporary filename while the file is being written
     */
    public function addRows($data, $modelId, $tempFilename)
    {
        $filename = $tempFilename . $this->fileExtension;
        $this->data = $data;
        $this->modelId = $modelId;
        $this->model = $this->getModel();
        if ($this->model) {

            if ($this->batch) {
                $rowNumber = $this->batch->getSessionVariable('rowNumber');
            } else {
                $this->_session = new \Zend_Session_Namespace(__CLASS__);
                $rowNumber = $this->_session->rowNumber;
            }

            if (empty($rowNumber)) {
                $rowNumber = 2;
            }

            $rows = $this->model->load();

            //$exportName = $this->getName();

            if (isset($this->data[$exportName]) && isset($this->data[$exportName]['format']) && in_array('formatVariable', $this->data[$exportName]['format'])) {
               $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
            }

            $excelObject = PHPExcel_IOFactory::load($filename);
            foreach($rows as $row) {
                $this->addRowWithCount($row, $excelObject, $rowNumber);
                $rowNumber++;
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($excelObject, "Excel2007");
        $objWriter->save($filename);

        if ($this->batch) {
            $this->batch->setSessionVariable('rowNumber', $rowNumber);
        } else {
            $this->_session->rowNumber = $rowNumber;
        }
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    public function addRowWithCount($row, $excelObject, $rowNumber)
    {
        $i=0;

        $exportRow = $this->filterRow($row);

        $activeSheet = $excelObject->getActiveSheet();

        $labeledCols = $this->getColumnHeaders();
        foreach($labeledCols as $columnName=>$label) {
            $cell = $this->getColumn($i) . $rowNumber;

            $activeSheet->setCellValue($cell, $exportRow[$columnName]);

            if ($excelDateFormat = $this->model->get($columnName, 'excelDateFormat')) {
                $activeSheet->getStyle($cell)->getNumberFormat()->setFormatCode($excelDateFormat);
            }

            $i++;
        }
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    public function addRow($row, $file)
    { }

    /**
     * Add a footer to a specific file
     * @param string $filename The temporary filename while the file is being written
     */
    public function addFooter($filename)
    {
    }

    /*protected function getCol($num) {
        $alphabet = range('A', 'Z');
        $numeric = ($num - 1) % 26;
        $letter = $alphabet[$numeric];
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return getNameFromNumber($num2) . $letter;
        } else {
            return $letter;
        }
    }*/

    protected function filterMultiOptions($result, $multiOptions)
    {
        if (is_array($multiOptions)) {
            /*
             *  Sometimes a field is an array and will be formatted later on using the
             *  formatFunction -> handle each element in the array.
             */
            if (is_array($result)) {
                foreach($result as $key => $value) {
                    if (array_key_exists($value, $multiOptions)) {
                        $result[$key] = $multiOptions[$value];
                    }
                }
            } else {
                if (array_key_exists($result, $multiOptions)) {
                    $result = $multiOptions[$result];
                }
            }
        }

        return $result;
    }

    protected function filterFormatFunction($value, $functionName)
    {
        if (!is_array($functionName) && method_exists($this, $functionName)) {
            return call_user_func(array($this, $functionName), $value);
        } else {
            return call_user_func($functionName, $value);
        }
    }

    /*protected function filterDateFormat($value, $dateFormat, $columnName)
    {
        $storageFormat = $this->model->get($columnName, 'storageFormat');
        return \MUtil_Date::format($result, $dateFormat, $storageFormat);
    }*/

    protected function filterDateFormat($value, $dateFormat, $columnName)
    {
        

        if ($value instanceof \Zend_Date) {
            $year = \MUtil_Date::format($value, 'yyyy');
            $month = \MUtil_Date::format($value, 'MM');
            $day = \MUtil_Date::format($value, 'dd');
            $hours = \MUtil_Date::format($value, 'HH');
            $minutes = \MUtil_Date::format($value, 'mm');
            $seconds = \MUtil_Date::format($value, 'ss');
        } else {
            $time = strtotime($value);

            $year = Date('Y', $time);
            $month = Date('m', $time);
            $day = Date('d', $time);
            $hours = Date('H', $time);
            $minutes = Date('i', $time);
            $seconds = Date('s', $time);
        }

        return PHPExcel_Shared_Date::FormattedPHPToExcel($year, $month, $day, $hours, $minutes, $seconds);
    }

    protected function filterItemDisplay($value, $functionName)
    {
        if (is_callable($functionName)) {
            $result = call_user_func($functionName, $value);
        } elseif (is_object($function)) {
            if (($function instanceof \MUtil_Html_ElementInterface)
                || method_exists($function, 'append')) {
                $object = clone $function;
                $result = $object->append($value);
            }
        } elseif (is_string($function)) {
            // Assume it is a html tag when a string
            $result = \MUtil_Html::create($function, $value);
        }

        return $result;
    }

    protected function filterHtml($result)
    {
        if ($result instanceof \MUtil_Html_ElementInterface) {
            if ($result->count() > 0) {
                $result = $result[0];
            } elseif ($result instanceof \MUtil_Html_AElement) {
                $href = $result->href;
                $result = $href[0];
            }
        }

        return $result;
    }

    /**
     * Filter the data in a row so that correct values are being used
     * @param  array $row a row in the model
     * @return array The filtered row
     */
    protected function filterRow($row)
    {
        $exportRow = array();
        foreach($row as $columnName=>$result) {
            if ($this->model->get($columnName, 'label')) {
                $options = $this->model->get($columnName, $this->modelFilterAttributes);


                foreach($options as $optionName => $optionValue) {
                    switch ($optionName) {
                        case 'multiOptions':
                            $result = $this->filterMultiOptions($result, $optionValue);
                            
                            break;

                        case 'formatFunction':
                            $result = $this->filterFormatFunction($result, $optionValue);
                            
                            break;

                        case 'dateFormat':

                            // if there is a formatFunction skip the date formatting
                            if (array_key_exists('formatFunction', $options)) {
                                continue;
                            }

                            $result = $this->filterDateFormat($result, $optionValue, $columnName);
                            
                            break;

                        case 'itemDisplay':

                            $result = $this->filterItemDisplay($result, $optionValue);

                        default:
                            break;
                    }
                }

                $result = $this->filterHtml($result);

                $exportRow[$columnName] = $result;
            }
        }
        return $exportRow;
    }


    protected function getColumn($x)
    {
        return PHPExcel_Cell::stringFromColumnIndex($x);
    }

    protected function getColumnHeaders()
    {
        $labeledCols = $this->model->getColNames('label');

        $columnHeaders = array();
        foreach($labeledCols as $columnName) {
            $columnHeaders[$columnName] = strip_tags($this->model->get($columnName, 'label'));
        }

        return $columnHeaders;
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
                    $options['excelDateFormat'] = 'dd-mm-yyyy';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $options['excelDateFormat'] = 'dd-mm-yyyy hh:mm:ss';
                    $options['excelCellSize'] = 20;
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $options['excelDateFormat'] = 'hh:mm:ss';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:
                    //$type                      = \MUtil_Model::TYPE_STRING;
                    //$options['formatFunction'] = 'formatString';
                    break;
            }
            $options['type']           = $type;
            $this->model->set($columnName, $options);
        }
    }
}