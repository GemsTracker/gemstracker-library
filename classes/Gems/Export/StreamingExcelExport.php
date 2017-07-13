<?php

/**
 *
 * @package    Pulse
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Export;


use Gems\Export\ExportAbstract;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Common\Type;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class StreamingExcelExport extends ExportAbstract
{
    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.xlsx';

    /**
     * @var integer     How many rows the batch will do in one go
     */
    protected $rowsPerBatch = 2000;

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'StreamingExcelExport';
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
                    'formatVariable'=> $this->_('Export labels instead of field names'),
                    'formatAnswer'  => $this->_('Format answers'),
                    'formatDate'    => $this->_('Format dates as Excel numbers easily convertable to date')
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
        $tempFilename = str_replace($this->fileExtension, '', $filename);

        $headerFilename = $tempFilename . '_header' . $this->fileExtension;

        $exportName = $this->getName();

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($headerFilename);

        $header = $this->getColumnHeaders();
        if (isset($this->data[$exportName]) &&
                isset($this->data[$exportName]['format']) &&
                in_array('formatVariable', (array) $this->data[$exportName]['format'])) {
            $writer->addRow($header);
        } else {
            $writer->addRow(array_keys($header));
        }

        $writer->close();
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
                $this->_session = new \Zend_Session_Namespace(__CLASS__);
                $rowNumber = $this->_session->rowNumber;
                $iteration = $this->_session->iteration;
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

            if (empty($iteration)) {
                $iteration = 0;
            }

            $filename = $tempFilename . '_' . $iteration . $this->fileExtension;

            $rows = $this->model->load();

            $exportName = $this->getName();

            if (isset($this->data[$exportName]) &&
                    isset($this->data[$exportName]['format']) &&
                    in_array('formatAnswer', (array) $this->data[$exportName]['format'])) {
                // We want answer labels instead of codes
            } else {
                // Skip formatting
               $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
            }

            $writer = WriterFactory::create(Type::XLSX);
            $writer->openToFile($filename);

            foreach($rows as $row) {
                $this->addRowWithCount($row, $writer, $rowNumber);
                $rowNumber++;
            }

            $writer->close();
        }

        if ($this->batch) {
            $this->batch->setSessionVariable('rowNumber', $rowNumber);
            $this->batch->setSessionVariable('iteration', $iteration+1);
        } else {
            $this->_session->rowNumber = $rowNumber;
            $this->_session->iteration = $iteration++;
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
        $writer->addRow($exportRow);
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
        $this->model = $this->getModel();
        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($filename);

        $tempFilename = str_replace($this->fileExtension, '', $filename);

        $headerFilename = $tempFilename . '_header' . $this->fileExtension;

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($headerFilename);

        $rowStyle = (new StyleBuilder())
           ->setFontBold()
           ->build();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $writer->addRowWithStyle($row, $rowStyle);
            }
        }
        $reader->close();
        unlink($headerFilename);

        if ($this->batch) {
            $iteration = $this->batch->getSessionVariable('iteration');
        } else {
            $this->_session = new \Zend_Session_Namespace(__CLASS__);
            $iteration = $this->_session->iteration;
        }

        for($i=0;$i<$iteration;$i++) {
            $partFilename = $tempFilename . '_' . $i . $this->fileExtension;

            $reader = ReaderFactory::create(Type::XLSX);
            $reader->open($partFilename);
            $reader->setShouldFormatDates(true);
            foreach($reader->getSheetIterator() as $sheetIndex=>$sheet) {
                if ($sheetIndex !== 1) {
                    $writer->addNewSheetAndMakeItCurrent();
                }

                foreach($sheet->getRowIterator() as $row) {
                    $writer->addRow($row);
                }
            }
            $reader->close();
            unlink($partFilename);
        }

        $writer->close();

        // reset row number and iteration
        if ($this->batch) {
            $this->batch->setSessionVariable('rowNumber', 0);
            $this->batch->setSessionVariable('iteration', 0);
        } else {
            $this->_session->rowNumber = 0;
            $this->_session->iteration = 0;
        }
    }

    protected function filterDateFormat($value, $dateFormat, $columnName)
    {
        $exportName = $this->getName();
        if (isset($this->data[$exportName]) &&
                isset($this->data[$exportName]['format']) &&
                in_array('formatDate', (array) $this->data[$exportName]['format'])) {
            if ($value instanceof \MUtil_Date) {
                $date = new \DateTime();
                $date->setTimestamp($value->getTimestamp());
                return $this->createExcelDate($date);
            } elseif ($this->validateDate($value, \MUtil_Date::$zendToPhpFormats[$dateFormat])) {
                $date = \DateTime::createFromFormat(\MUtil_Date::$zendToPhpFormats[$dateFormat], $value);
                return $this->createExcelDate($date);
            }
        }

        return parent::filterDateFormat($value, $dateFormat, $columnName);
    }

    /**
     * @param $value string Date value
     * @param $dateFormat string date format as in the DateTime php class
     * @return bool True if Date is valid
     */
    public function validateDate($value, $dateFormat)
    {
        $dateTime = \DateTime::createFromFormat($dateFormat, $value);
        if ($dateTime) {
            $writtenDate = str_replace('!', '', $dateTime->format($dateFormat));
            return $writtenDate === $value;
        }

        return false;
    }

    /**
     * Create Excel date stamp from DateTime
     *
     * @param \DateTime $date
     * @return float number of days since 1900-01-00
     */
    public function createExcelDate(\DateTime $date)
    {
        $day = clone $date;
        $endDate = $day->setTime(0, 0, 0);
        $startDate = new \DateTime('1970-01-01 00:00:00');
        $diff = $endDate->diff($startDate)->format('%a');

        if ($endDate < $startDate) {
            $daysBetween = 25569 - $diff;
        } else {
            $daysBetween = 25569 + $diff;
        }

        $seconds = $date->getTimestamp() - $endDate->getTimestamp();

        return (float)$daysBetween + ($seconds / 86400);
    }

    protected function filterHtml($result)
    {
        $result = parent::filterHtml($result);

        if (is_numeric($result)) {
            if (is_int($result)) {
                $result = (int) $result;
            } else {
                $result = (double) $result;
            }
        }

        return $result;
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
                    $options['dateFormat']    = 'yyyy-MM-dd';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $options['dateFormat']    = 'yyyy-MM-dd HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $options['dateFormat']    = 'HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    break;

                //When no type set... assume string
                /*case \MUtil_Model::TYPE_STRING:
                default:
                    $type                      = \MUtil_Model::TYPE_STRING;
                    $options['formatFunction'] = 'formatString';
                    break;*/
            }
            $options['type']           = $type;
            $this->model->set($columnName, $options);
        }
    }
}