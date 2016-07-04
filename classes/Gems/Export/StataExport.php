<?php

namespace Gems\Export;

use DomDocument;

class StataExport extends ExportAbstract
{

    protected $stataFileVersion = 117;

    protected $maxStringLength = 2045;
    protected $defaultStringLength = 100;

    /**
     * @var integer     How many rows the batch will do in one go
     */
    protected $rowsPerBatch = 100;

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.xml';

    /**
     * @var array   Array with the filter options that should be used for this exporter
     */
    protected $modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'Stata Export';
    }

    /**
     * Add a footer to a specific file
     * @param string $filename The temporary filename while the file is being written
     */
    public function addFooter($filename) {
        $file = new DOMDocument();
        $file->preserveWhiteSpace = false;
        $file->formatOutput = true;
        $file->load($filename);

        $nobs = $file->getElementsByTagName('nobs');
        $typelist = $file->getElementsByTagName('type');
        $fmtlist = $file->getElementsByTagName('fmt');

        if ($this->batch) {
            $rowNumber = $this->batch->getSessionVariable('rowNumber');
        } else {
            $this->_session = new \Zend_Session_Namespace(__CLASS__);
            $rowNumber = $this->_session->rowNumber;
        }

        $nobs->item(0)->nodeValue = $rowNumber;

        if ($this->batch) {
            $stringSizes = $this->batch->getSessionVariable('stringSizes');
        } else {
            $stringSizes = $this->_session->stringSizes;
        }

        if (count($typelist)) {
            foreach($typelist as $type) {
                $columnName = $type->getAttribute('varname');

                if (isset($stringSizes[$columnName])) {
                    $size = $stringSizes[$columnName];
                    if ($size > $this->maxStringLength) {
                        $size = $this->maxStringLength;
                    }
                    $value = 'str' . $size;
                    $type->nodeValue = $value;
                }
            }
        }

        if (count($fmtlist)) {
            foreach($fmtlist as $fmt) {
                $columnName = $fmt->getAttribute('varname');

                if (isset($stringSizes[$columnName])) {
                    $size = $stringSizes[$columnName];
                    if ($size > $this->maxStringLength) {
                        $size = $this->maxStringLength;
                    }
                    $value = '%' . $size . 's';
                    $fmt->nodeValue = $value;
                }
            }
        }
        $file->save($filename);

        // reset rowNumber
        if ($this->batch) {
            $this->batch->setSessionVariable('rowNumber', 0);
        } else {
            $this->_session->rowNumber = 0;
        }
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    protected function addHeader($filename)
    {
        $xml = new DOMDocument('1.0', 'US-ASCII');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $dta = $xml->createElement('dta');
        $xml->appendChild($dta);



        $header = $xml->createElement('header');
        $dta->appendChild($header);

        $ds_format = $xml->createElement('ds_format', $this->stataFileVersion);
        $header->appendChild($ds_format);

        $byteorder = $xml->createElement('byteorder', 'LOHI');
        $header->appendChild($byteorder);
        $filetype = $xml->createElement('filetype', 1);
        $header->appendChild($filetype);


        $columnHeaders = $this->getColumnHeaders();

        $nvar = $xml->createElement('nvar', count($columnHeaders));
        $header->appendChild($nvar);

        $model = $this->getModel();
        $recordcount = $model->loadPaginator()->getTotalItemCount();

        $nobs = $xml->createElement('nobs', $recordcount);
        $header->appendChild($nobs);

        $data_name = $this->filename . $this->fileExtension;

        $data_label = $xml->createElement('data_label', $data_name);
        $header->appendChild($data_label);

        $time_stamp = $xml->createElement('time_stamp', date('d M Y H:i'));
        $header->appendChild($time_stamp);

        $descriptors = $xml->createElement('descriptors');
        $dta->appendChild($descriptors);

        $typelist = $xml->createElement('typelist');
        $descriptors->appendChild($typelist);
        $varlist = $xml->createElement('varlist');
        $descriptors->appendChild($varlist);
        $fmtlist = $xml->createElement('fmtlist');
        $descriptors->appendChild($fmtlist);
        $lbllist = $xml->createElement('lbllist');
        $descriptors->appendChild($lbllist);

        $variableLabels = $xml->createElement('variable_labels');
        $dta->appendChild($variableLabels);


        $data = $xml->createElement('data');
        $dta->appendChild($data);

        $valueLabels = $xml->createElement('value_labels');
        $dta->appendChild($valueLabels);

        foreach($columnHeaders as $colname => $columnHeader) {

            $variableName = $this->model->get($colname, 'variableName');

            $typeElement = $xml->createElement('type', $columnHeader['type']);
            $typeElement->setAttribute('varname', $variableName);
            $typelist->appendChild($typeElement);

            $variableElement = $xml->createElement('variable');
            $variableElement->setAttribute('varname', $variableName);
            $varlist->appendChild($variableElement);

            $fmtElement = $xml->createElement('fmt', $columnHeader['format']);
            $fmtElement->setAttribute('varname', $variableName);
            $fmtlist->appendChild($fmtElement);

            if (isset($columnHeader['multiOptions'])) {


                $multiOptionName = 'val'.$variableName;

                $lblnameElement = $xml->createElement('lblname', $multiOptionName);

                $vallabElement = $xml->createElement('vallab');
                $vallabElement->setAttribute('name', $multiOptionName);
                $valueLabels->appendChild($vallabElement);

                $multiOptions = $columnHeader['multiOptions'];

                $numeric = true;
                foreach($multiOptions as $key=>$value) {
                    if (!is_numeric($key)) {
                        $numeric = false;
                    }
                }

                $i=0;
                foreach($multiOptions as $key=>$value) {
                    $labelElement = $xml->createElement('label', $value);
                    if ($numeric) {
                        $labelElement->setAttribute('value', $key);
                    } else {
                        $labelElement->setAttribute('value', $i);
                    }
                    $vallabElement->appendChild($labelElement);
                    $i++;
                }

            } else {
                $lblnameElement = $xml->createElement('lblname');
            }
            $lblnameElement->setAttribute('varname', $variableName);
            $lbllist->appendChild($lblnameElement);

            $cleanLabel = utf8_encode(strip_tags(html_entity_decode($columnHeader['label'])));

            $vlabelElement = $xml->createElement('vlabel', $cleanLabel);
            $vlabelElement->setAttribute('varname', $variableName);
            $variableLabels->appendChild($vlabelElement);


        }


        $xml->save($this->tempFilename.$this->fileExtension);
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
        $this->model = $this->getModel();
        
        $this->model->setFilter($filter + $this->model->getFilter());

        $rows = $this->model->load();

        if ($this->batch) {
            $rowNumber = $this->batch->getSessionVariable('rowNumber');
        } else {
            $this->_session = new \Zend_Session_Namespace(__CLASS__);
            $rowNumber = $this->_session->rowNumber;
        }

        if (empty($rowNumber)) {
            $rowNumber = 0;
        }

        $filename = $tempFilename . $this->fileExtension;

        $file = new DOMDocument();
        $file->preserveWhiteSpace = false;
        $file->formatOutput = true;
        $file->load($filename);

        if ($this->model->getMeta('nested', false)) {
            $nestedNames = $this->model->getMeta('nestedNames');
            $rows = $this->addNestedRows($rows, $nestedNames);
        }

        foreach($rows as $row) {
            $this->addRowWithCount($row, $file, $rowNumber);
            $rowNumber++;
        }

        $file->save($filename);

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
    public function addRowWithCount($row, $file, $rowNumber)
    {
        $exportRow = $this->filterRow($row);

        $rowElement = $file->createElement('o');
        $rowElement->setAttribute('num', $rowNumber);
        $dataElement = $file->getElementsByTagName('data')->item(0);

        $dataElement->appendChild($rowElement);

        $labeledCols = $this->model->getColNames('label');

        if ($this->batch) {
            $stringSizes = $this->batch->getSessionVariable('stringSizes');
        } else {
            $stringSizes = $this->_session->stringSizes;
        }

        foreach($labeledCols as $columnName) {

            $variableName = $this->model->get($columnName, 'variableName');
            $type = $this->model->get($columnName, 'type');

            if (($type == \MUtil_Model::TYPE_DATE || $type == \MUtil_Model::TYPE_DATETIME) && $exportRow[$columnName] !== null) {

                if ($exportRow[$columnName] instanceof \Zend_Date) {
                    $exportRow[$columnName] = $exportRow[$columnName]->getTimestamp() * 1000 + 315619200000;
                } else {
                    $exportRow[$columnName] = strtotime($exportRow[$columnName] . ' GMT') * 1000 + 315619200000;
                }

            }
            if ($type == \MUtil_Model::TYPE_STRING && !$this->model->get($columnName, 'multiOptions')) {
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

            $variableElement = $file->createElement('v', $exportRow[$columnName]);
            $variableElement->setAttribute('varname', $variableName);
            $rowElement->appendChild($variableElement);
        }
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    public function addRow($row, $file)
    { }

    protected function getColumnHeaders()
    {
        $model = $this->getModel();
        $labeledCols = $this->model->getColNames('label');

        $columnHeaders = array();

        $stringSizes = array();

        foreach($labeledCols as $colname) {
            //$columnHeaders[$colname]['name'] = $this->fixName($colname);
            $options = array();

            $variableName = $model->get($colname, 'variableName');

            $type = $model->get($colname, 'type');

            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $type = 'double';
                    $format = '%tcDDmonCCYY';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $type = 'double';
                    $format = '%tcDDmonCCYY';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $type = 'double';
                    $format = '%tcDDmonCCYY';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    $type        = 'double';
                    $format      = '%10.0g';
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:

                    $type        = 'str' . 1;
                    $format      = '%' . 1 . 's';
                    $stringSizes[$variableName] = 1;

                    break;
            }

            $columnHeaders[$colname]['type'] = $type;
            $columnHeaders[$colname]['format'] = $format;
            $columnHeaders[$colname]['label'] = $model->get($colname, 'label');

            if ($options = $model->get($colname, 'multiOptions')) {

                $columnHeaders[$colname]['multiOptions'] = $options;
                $columnHeaders[$colname]['type'] = 'double';
                $columnHeaders[$colname]['format'] = '%10.0g';
                unset($stringSizes[$variableName]);
            }
        }

        if ($this->batch) {
            $this->batch->setSessionVariable('stringSizes', $stringSizes);
        } else {
            $this->_session->stringSizes = $stringSizes;
        }

        return $columnHeaders;
    }

    /**
     * Finalizes the files stored in $this->files.
     * If it has 1 file, it will return that file, if it has more, it will return a zip containing all the files, named as the first file in the array.
     * @return File with download headers
     */
    public function finalizeFiles()
    {
        parent::finalizeFiles();

        if (!$this->batch) {
            $this->_session->rowNumber = 0;
        }
    }

    /**
     * Make sure the $input fieldname is correct for usage in SPSS
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
        $newColNames = array();
        foreach($labeledCols as $columnName) {
            $options = array();
            $this->model->remove($columnName, 'dateFormat');
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    break;

                case \MUtil_Model::TYPE_DATETIME:

                    break;

                case \MUtil_Model::TYPE_TIME:
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    break;

                case \MUtil_Model::TYPE_CHILD_MODEL;
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