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

        $columnHeaders = $this->getColumnHeaders();
        $row = 1;

        $i=0;
        foreach($columnHeaders as $columnHeader) {
            $column = $this->getColumn($i);
            $cell = $column . $row;
            $excelObject->getActiveSheet()->setCellValue($cell, $columnHeader);
            $excelObject->getActiveSheet()->setCellValue($cell, $columnHeader);
            $excelObject->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
            $i++;
        }

        $excelObject->getActiveSheet()->getStyle("A1:$cell")->getFont()->setBold(true);

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
                $this->session = new \Zend_Session_Namespace(__CLASS__);
                $rowNumber = $this->session->rowNumber;
            }

            if (empty($rowNumber)) {
                $rowNumber = 2;
            }

            $rows = $this->model->load();

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
            $this->session->rowNumber = $rowNumber;
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

        $labeledCols = $this->getColumnHeaders();
        foreach($labeledCols as $colName=>$label) {
            $cell = $this->getColumn($i) . $rowNumber;
            $excelObject->getActiveSheet()->setCellValue($cell, $exportRow[$colName]);
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

    protected function getColumn($x)
    {
        return PHPExcel_Cell::stringFromColumnIndex($x);
    }

    protected function getColumnHeaders()
    {
        $labeledCols = $this->model->getColNames('label');

        $columnHeaders = array();
        foreach($labeledCols as $colName) {
            $columnHeaders[$colName] = $this->model->get($colName, 'label');
        }

        return $columnHeaders;
    }

    /**
     * Preprocess the model to add specific options
     */
    protected function preprocessModel()
    {
    }
}