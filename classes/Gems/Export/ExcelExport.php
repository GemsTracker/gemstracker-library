<?php

/**
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
     * @param array  $filter                    Filter (limit) to use
     */
    public function addRows($data, $modelId, $tempFilename, $filter)
    {
        $filename = $tempFilename . $this->fileExtension;
        $this->data = $data;
        $this->modelId = $modelId;
        $this->model = $this->getModel();
        
        $this->model->setFilter($filter + $this->model->getFilter());
        if ($this->model) {

            if ($this->batch) {
                $rowNumber = $this->batch->getSessionVariable('rowNumber');
            } else {
                $this->_session = new \Zend_Session_Namespace(__CLASS__);
                $rowNumber = $this->_session->rowNumber;
            }
            
            // Reset internal rownumber when we move to a new file
            if ($filter = $this->model->getFilter()) {
                if (array_key_exists('limit', $filter)) {
                    if ($filter['limit'][1] == 0) {
                        $rowNumber = 2;
                    }
                }
            }
            
            if (empty($rowNumber)) {
                $rowNumber = 2;
            }

            $rows = $this->model->load();
            
            $exportName = $this->getName();

            if (isset($this->data[$exportName]) && isset($this->data[$exportName]['format']) && in_array('formatAnswer', $this->data[$exportName]['format'])) {
                // We want answer labels instead of codes
            } else {
                // Skip formatting 
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

        $labeledCols = $this->getLabeledColumns();
        
        foreach($labeledCols as $columnName) {
            // We could be missing data for a column, just skip it
            if (array_key_exists($columnName, $exportRow)) {
                $cell = $this->getColumn($i) . $rowNumber;

                $activeSheet->setCellValue($cell, $exportRow[$columnName]);

                if ($excelDateFormat = $this->model->get($columnName, 'excelDateFormat')) {
                    $activeSheet->getStyle($cell)->getNumberFormat()->setFormatCode($excelDateFormat);
                }
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

    protected function getColumn($x)
    {
        return PHPExcel_Cell::stringFromColumnIndex($x);
    }

    protected function getColumnHeaders()
    {
        $labeledCols = $this->getLabeledColumns();
        
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
        parent::preprocessModel();
        
        $labeledCols = $this->getLabeledColumns();
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