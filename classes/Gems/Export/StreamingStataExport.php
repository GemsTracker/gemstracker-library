<?php

namespace Gems\Export;

use MUtil\Model;

use XMLWriter;
/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class StreamingStataExport extends ExportAbstract
{
    protected $stataFileVersion = 117;

    protected $maxStringLength = 2045;
    protected $defaultStringLength = 100;


    protected $nestedRowsPerBatch = 10;

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.xml';

    /**
     * @var array   Array with the filter options that should be used for this exporter
     */
    protected $modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');

    /**
     * @var integer     How many rows the batch will do in one go
     */
    protected $rowsPerBatch = 100;

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'StreamingStataExport';
    }

    /**
     * Add an export command with specific details. Can be batched.
     *
     * COPIED from ExportAbstract to add $modelId to footer.
     * 
     * @param array $data    Data submitted by export form
     * @param array $modelId Model Id when multiple models are passed
     */
    public function addExport($data, $modelId = false)
    {

        $this->files   = $this->getFiles();
        $this->data    = $data;
        $this->modelId = $modelId;

        if ($model = $this->getModel()) {
            if ($this->model->getMeta('nested', false)) {
               $this->rowsPerBatch = $this->nestedRowsPerBatch;
            }
            
            $totalRows  = $this->getModelCount();
            $this->addFile();
            $this->addHeader($this->tempFilename . $this->fileExtension);
            $currentRow = 0;
            do {
                $filter['limit'] = array($this->rowsPerBatch, $currentRow);
                if ($this->batch) {
                    $this->batch->addTask('Export\\ExportCommand', $data['type'], 'addRows', $data, $modelId, $this->tempFilename, $filter);
                } else {
                    $this->addRows($data, $modelId, $this->tempFilename, $filter);
                }
                $currentRow = $currentRow + $this->rowsPerBatch;
            } while ($currentRow < $totalRows);

            if ($this->batch) {
                $this->batch->addTask('Export\\ExportCommand', $data['type'], 'addFooter', $this->tempFilename . $this->fileExtension, $modelId, $data);
                $this->batch->setSessionVariable('files', $this->files);
            } else {
                $this->addFooter($this->tempFilename . $this->fileExtension, $modelId, $data);
                $this->_session->files = $this->files;
            }
        }
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    protected function addheader($filename)
    {
        //Probably not much as most needs to be added after
    }

    public function addNestedRows($rows, $nestedNames)
    {
        $flattenedRows = array();
        foreach($rows as $row) {
            $flattenedRow = array();
            $newRows = array($row);
            foreach($nestedNames as $nestedModelName) {
                $subrows = $row[$nestedModelName];
                $subrowNumber = count($subrows);
                foreach($subrows as $subrow) {
                    foreach($newRows as $newRow) {
                        $tempRow = array_merge($newRow, $subrow);
                        unset($tempRow[$nestedModelName]);
                        $flattenedRow[] = $tempRow;
                    }
                }
                $newRows = $flattenedRow;
                $flattenedRow = array();
            }
            foreach($newRows as $newRow) {
                $flattenedRows[] = $newRow;
            }
        }
        return $flattenedRows;
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
        $this->data = $data;
        $this->modelId = $modelId;
        $this->model = $this->getModel();
        
        
        if ($this->model) {
            $this->model->setFilter($filter + $this->model->getFilter());
            
            if ($this->batch) {
                $rowNumber = $this->batch->getSessionVariable('rowNumber');
                $iteration = $this->batch->getSessionVariable('iteration');
            } else {
                $rowNumber = $this->_session->rowNumber;
                $iteration = $this->_session->iteration;
            }
            
            // Reset internal rownumber when we move to a new file
            if ($filter = $this->model->getFilter()) {
                if (array_key_exists('limit', $filter)) {
                    if ($filter['limit'][1] == 0) {
                        $rowNumber = 0;
                    }
                }
            }
            
            if (empty($rowNumber)) {
                $rowNumber = 0;
            }

            if (empty($iteration)) {
                $iteration = 0;
            }
            
            $filename = $tempFilename . '_' . $iteration . $this->fileExtension;

            $rows = $this->model->load();
            
            $exportName = $this->getName();

            $writer = new XMLWriter();
            $writer->openURI($filename);
            $writer->setIndent(true);

            if ($this->model->getMeta('nested', false)) {
                $nestedNames = $this->model->getMeta('nestedNames');
                $rows = $this->addNestedRows($rows, $nestedNames);
            }
            
            foreach($rows as $row) {
                $this->addRowWithCount($row, $writer, $rowNumber);
                $rowNumber++;
            }
        }

        if ($this->batch) {
            $this->batch->setSessionVariable('rowNumber', $rowNumber);
            $this->batch->setSessionVariable('iteration', ++$iteration);
        } else {
            $this->_session->rowNumber = $rowNumber;
            $this->_session->iteration = ++$iteration;
        }
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    public function addRowWithCount($row, $writer, $rowNumber)
    {
        $exportRow   = $this->filterRow($row);
        $labeledCols = $this->getLabeledColumns();
        $exportRow   = array_replace(array_flip($labeledCols), $exportRow);
        $writer->startElement('o');
        $writer->writeAttribute('num', $rowNumber);

        if ($this->batch) {
            $stringSizes = $this->batch->getSessionVariable('stringSizes');
        } else {
            $stringSizes = $this->_session->stringSizes;
        }

        foreach($labeledCols as $columnName) {

            $variableName = $this->model->get($columnName, 'variableName');
            $type = $this->model->get($columnName, 'type');

            if (($type == Model::TYPE_DATE || $type == Model::TYPE_DATETIME) && $exportRow[$columnName] !== null) {

                if (!empty($exportRow[$columnName])) {
                    if ($exportRow[$columnName] instanceof \DateTimeInterface) {
                        if ($type == Model::TYPE_DATE) {
                            $exportRow[$columnName] = floor(($exportRow[$columnName]->getTimestamp() + 315619200) / 86400);
                        } else {
                            $exportRow[$columnName] = ($exportRow[$columnName]->getTimestamp() + 315619200) * 1000;
                        }

                    } else {
                        if ($type == Model::TYPE_DATE) {
                            $exportRow[$columnName] = floor((strtotime($exportRow[$columnName] . ' GMT') + 315619200) / 86400);
                        } else {
                            $exportRow[$columnName] = (strtotime($exportRow[$columnName] . ' GMT') + 315619200) * 1000 ;
                        }
                    }
                }
            }
            if ($type == Model::TYPE_STRING && !$this->model->get($columnName, 'multiOptions')) {
                $size = strlen($exportRow[$columnName]);

                if ((!isset($stringSizes[$variableName]) || ($stringSizes[$variableName] < $size)) && $size > 0) {
                    $stringSizes[$variableName] = $size;

                    if ($this->batch) {
                        $this->batch->setSessionVariable('stringSizes', $stringSizes);
                    } else {
                        $this->_session->stringSizes = $stringSizes;
                    }
                }
            }

            if ($multiOptions = $this->model->get($columnName, 'multiOptions')) {
                if ($exportRow[$columnName] !== null) {

                    $numeric = true;
                    $newValue = 0;
                    $i=0;



                    foreach($multiOptions as $key=>$value) {
                        if (!is_numeric($key)) {
                            $numeric = false;
                        }
                        if ($key == $exportRow[$columnName]) {
                            $newValue = $i;
                        }
                    }

                    if($numeric) {

                        $newValue = $exportRow[$columnName];
                        $exportRow[$columnName] = $newValue;
                    } else {
                        $newMultiOptions = array_keys($multiOptions);
                        $newValue = array_search($exportRow[$columnName], $newMultiOptions);
                        $exportRow[$columnName] = $newValue;
                    }

                }
            }

            $writer->startElement('v');
            $writer->writeAttribute('varname', $variableName);
            if (is_array($exportRow[$columnName])) {
                $exportRow[$columnName] = join(', ', $exportRow[$columnName]);
            }
            $writer->text($exportRow[$columnName]);
            // End v
            $writer->endElement();

        }
        // End o
        $writer->endElement();

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
    public function addFooter($filename, $modelId = null, $data = null)
    {   
        $tempFilename = str_replace($this->fileExtension, '', $filename);
        $this->model = $this->getModel();
        $this->modelId = $modelId;

        if ($this->batch) {
            $iteration = $this->batch->getSessionVariable('iteration');
        } else {
            $iteration = $this->_session->iteration;
        }

        $writer = new XMLWriter();
        $writer->openURI($filename);
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        //Added padding comparable to xml output;
        $writer->startElement('dta');

            $this->createHeaderFile($writer, $filename);

            $writer->startElement('data');

            for($i=0;$i<$iteration;$i++) {
                $partFilename = $tempFilename . '_' . $i . $this->fileExtension;

                $data = file_get_contents($partFilename);
                $writer->writeRaw($data);
                unlink($partFilename);
            }

            $writer->endElement();

        $writer->endElement();

        // reset rowNumber
        if ($this->batch) {
            $this->batch->setSessionVariable('rowNumber', 0);
            $this->batch->setSessionVariable('iteration', 0);
            $this->batch->setSessionVariable('stringSizes', array());
        } else {
            $this->_session->rowNumber = 0;
            $this->_session->iteration = 0;
            $this->_session->stringSizes = array();
        }
    }

    protected function createHeaderFile(XMLWriter $writer, $filename)
    {
        $columnHeaders = $this->getColumnHeaders();       

        if ($this->batch) {
            $rowNumber = $this->batch->getSessionVariable('rowNumber');
            $stringSizes = $this->batch->getSessionVariable('stringSizes');
            $files = $this->batch->getSessionVariable('files');

        } else {
            $rowNumber = $this->_session->rowNumber;
            $stringSizes = $this->_session->stringSizes;
            $files = $this->_session->files;
        }

        $finalFiles = array_flip($files);

        if (isset($finalFiles[$filename])) {
            $finalFilename = $finalFiles[$filename];
        } else {
            $finalFilename = 'unknown' . $this->fileExtension;
        }        

        $writer->startElement('header');
            $writer->startElement('ds_format');
                $writer->text($this->stataFileVersion);
            $writer->endElement();

            $writer->startElement('byteorder');
                $writer->text('LOHI');
            $writer->endElement();

            $writer->startElement('filetype');
                $writer->text(1);
            $writer->endElement();

            $writer->startElement('nvar');
                $writer->text(count($columnHeaders));
            $writer->endElement();

            $writer->startElement('nvar');
                $writer->text(count($columnHeaders));
            $writer->endElement();

            $writer->startElement('nobs');
                $writer->text($rowNumber);
            $writer->endElement();

            $writer->startElement('data_label');
                $writer->text($finalFilename);
            $writer->endElement();

            $writer->startElement('time_stamp');
                $writer->text(date('d M Y H:i'));
            $writer->endElement();
        // ending header
        $writer->endElement();

        $writer->startElement('descriptors');

            $writer->startElement('typelist');
                foreach($columnHeaders as $colname => $columnHeader) {
                    $variableName = $this->model->get($colname, 'variableName');

                    $writer->startElement('type');
                        $writer->writeAttribute('varname', $variableName);
                        $writer->text($columnHeader['type']);
                    $writer->endElement();
                }
            $writer->endElement();

            $writer->startElement('varlist');
                foreach($columnHeaders as $colname => $columnHeader) {
                    $variableName = $this->model->get($colname, 'variableName');

                    $writer->startElement('variable');
                        $writer->writeAttribute('varname', $variableName);
                    $writer->endElement();
                }
            $writer->endElement();

            $writer->startElement('fmtlist');
                foreach($columnHeaders as $colname => $columnHeader) {
                    $variableName = $this->model->get($colname, 'variableName');

                    $writer->startElement('fmt');
                        $writer->writeAttribute('varname', $variableName);
                        $writer->text($columnHeader['format']);
                    $writer->endElement();
                }
            $writer->endElement();

            $writer->startElement('lbllist');
                foreach($columnHeaders as $colname => $columnHeader) {
                    $variableName = $this->model->get($colname, 'variableName');

                    $writer->startElement('lblname');
                        $writer->writeAttribute('varname', $variableName);

                        if (isset($columnHeader['multiOptions'])) {
                            $multiOptionName = 'val'.$variableName;
                            $writer->text($multiOptionName);
                        }
                    $writer->endElement();
                }
            $writer->endElement();
        // ending descriptors
        $writer->endElement();

        $writer->startElement('variable_labels');
            foreach($columnHeaders as $colname => $columnHeader) {
                $cleanLabel = utf8_encode(strip_tags(html_entity_decode($columnHeader['label'])));

                $writer->startElement('vlabel');
                    $writer->writeAttribute('varname', $colname);
                    $writer->text($cleanLabel);
                $writer->endElement();
            }
        $writer->endElement();


        $writer->startElement('value_labels');
            foreach($columnHeaders as $colname => $columnHeader) {
                if (isset($columnHeader['multiOptions'])) {
                    $variableName = $this->model->get($colname, 'variableName');
                    $multiOptionName = 'val'.$variableName;
                    $writer->startElement('vallab');
                    $writer->writeAttribute('name', $multiOptionName);

                    $numeric = true;
                    foreach($columnHeader['multiOptions'] as $key=>$value) {
                        if (!is_numeric($key)) {
                            $numeric = false;
                        }
                    }

                    $i=0;
                    foreach($columnHeader['multiOptions'] as $key=>$value) {
                        $writer->startElement('label');
                        if ($numeric) {
                            $writer->writeAttribute('value', $key);
                        } else {
                            $writer->writeAttribute('value', $i);
                        }
                        $writer->text($value);
                        $i++;
                        $writer->endElement();
                    }
                    $writer->endElement();
                }
            }
        $writer->endElement();
    }

    protected function filterHtml($result)
    {
        if (is_numeric($result)) {
            if (is_int($result)) {
                $result = (int) $result;
            } else {
                $result = (double) $result;
            }
        }

        $result = parent::filterHtml($result);

        if ($result instanceof \DateTimeInterface) {
            $result = $result->format('Y-m-d H:i:s');
        }

        return $result;
    }

    /**
     * Make sure the $input fieldname is correct for usage in Stata
     *
     * Should start with alphanum, and contain no spaces
     *
     * @param string $input
     * @return string
     */
    public function fixName($input)
    {
        if (!preg_match("/^([a-z]|[A-Z])+.*$/", $input)) //var starting with a number?
        {
            $input = "v" . $input; //add a leading 'v'
        }
        $input = str_replace(array(
            "-",
            ":",
            ";",
            "!",
            "[",
            "]",
            " "
        ), array(
            "_",
            "_dd_",
            "_dc_",
            "_excl_",
            "_",
            "",
            "_"
        ), $input);
        return $input;
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
        $output = str_replace(array("'", "\r", "\n"), array("''", ' ', ' '), $input);
        return $output;
    }

    protected function getColumnHeaders()
    {
        $model = $this->getModel();
        $labeledCols = $this->getLabeledColumns();

        $columnHeaders = array();

        if ($this->batch) {
            $stringSizes = $this->batch->getSessionVariable('stringSizes');
        } else {
            $stringSizes = $this->_session->stringSizes;
        }

        foreach($labeledCols as $colname) {
            //$columnHeaders[$colname]['name'] = $this->fixName($colname);
            $options = array();

            $variableName = $model->get($colname, 'variableName');

            $type = $model->get($colname, 'type');

            switch ($type) {
                case Model::TYPE_DATE:
                    $type = 'double';
                    $format = '%tdDDmonCCYY';
                    break;

                case Model::TYPE_DATETIME:
                    $type = 'double';
                    $format = '%tcDDmonCCYY';
                    break;

                case Model::TYPE_TIME:
                    $type = 'double';
                    $format = '%tcDDmonCCYY';
                    break;

                case Model::TYPE_NUMERIC:
                    $type        = 'double';
                    $format      = '%10.0g';
                    break;

                //When no type set... assume string
                case Model::TYPE_STRING:
                default:

                    if (isset($stringSizes[$variableName])) {
                        $stringSize = $stringSizes[$variableName];
                    } else {
                        $stringSize = 1;    
                    }
                    $type        = 'str' . $stringSize;
                    $format      = '%' . $stringSize . 's';
                    

                    break;
            }

            $columnHeaders[$colname]['type'] = $type;
            $columnHeaders[$colname]['format'] = $format;
            $columnHeaders[$colname]['label'] = $model->get($colname, 'label');

            if ($options = $model->get($colname, 'multiOptions')) {

                $columnHeaders[$colname]['multiOptions'] = $options;
                $columnHeaders[$colname]['type'] = 'double';
                $columnHeaders[$colname]['format'] = '%10.0g';
            }
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
        $newColNames = array();
        foreach($labeledCols as $columnName) {
            $options = array();
            $this->model->remove($columnName, 'dateFormat');
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case Model::TYPE_DATE:
                    break;

                case Model::TYPE_DATETIME:
                    break;

                case Model::TYPE_TIME:
                    break;

                case Model::TYPE_NUMERIC:
                    break;

                case Model::TYPE_CHILD_MODEL;
                    break;

                //When no type set... assume string
                case Model::TYPE_STRING:
                default:
                    $type                      = Model::TYPE_STRING;
                    $options['formatFunction'] = 'formatString';
                    break;
            }
            $options['type']           = $type;
            $this->model->set($columnName, $options);

            if ($multiOptions = $this->model->get($columnName, 'multiOptions')) {
                $keys = array_keys($multiOptions);
                if ($keys ==  array('Y', 'N') || $keys == array('Y', '')) {
                    $multiOptions = array_reverse($multiOptions);
                    $this->model->set($columnName, 'multiOptions', $multiOptions);
                }
            }

            // A variable name in stata has a max of 32 characters. If it's longer, get a unique shorter version.
            $colName = $this->fixName($columnName);
            if (strlen($colName) > 32) {
                $colName = substr($colName, 0, 32);
                $i = 1;
                while (isset($newColNames[$colName])) {
                    $varLength = 32 - strlen($i);
                    $colName = substr($colName, 0, $varLength) . $i;
                }
            }
            $newColNames[$colName] = true;
            $this->model->set($columnName, 'variableName', $colName);
        }
    }
}