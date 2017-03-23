<?php

/**
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
abstract class ExportAbstract extends \MUtil_Translate_TranslateableAbstract
{
    /**
     * @var \Zend_Session_Namespace    Own session used for non-batch exports
     */
    protected $_session;

    /**
     * @var \Gems_Task_TaskRunnerBatch   The batch object if one is set
     */
    protected $batch;

    /**
     * @var array   Data submitted by export form
     */
    protected $data;

    /**
     * @var string  The temporary filename while the file is being written
     */
    protected $tempFilename;

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension;

    /**
     * @var string     The export file name, how it should be downloaded
     */
    protected $filename;

    /**
     * @var array   Array of all the filenames, new_name => temp_name
     */
    protected $files;

    /**
     * @var array   Model filters for export
     */
    protected $filter;

    /**
     * @var array   Array of the loaded first row of the model
     */
    protected $firstRow;

    /**
     * @var array   Array with the filter options that should be used for this exporter
     */
    protected $modelFilterAttributes = array('multiOptions', 'formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');

    /**
     *
     * @var integer Model Id for when multiple models are passed
     */
    protected $modelId;

    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \MUtil_Model_ModelAbstract  Current model to export
     */
    protected $model;

    /**
     * @var array Filter settings of the current loaded model
     */
    protected $modelFilter;

    /**
     * @var integer     How many rows the batch will do in one go
     */
    protected $rowsPerBatch = 500;

    /**
     * @return string name of the specific export
     */
    abstract public function getName();

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil_Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(&$form, &$data) {}

    /**
     * Returns an array of ordered columnnames that have a label
     *
     * @return array Array of columnnames
     */
    public function getLabeledColumns()
    {
        if (!$this->model->hasMeta('labeledColumns')) {
            $orderedCols = $this->model->getItemsOrdered();

            $results = array();
            foreach ($orderedCols as $name) {
                if ($this->model->has($name, 'label')) {
                    $results[] = $name;
                }
            }

            $this->model->setMeta('labeledColumns', $results);
        }

        return $this->model->getMeta('labeledColumns');
    }

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues() {}
        
    /**
     * Add an export command with specific details. Can be batched.
     * @param array $data    Data submitted by export form
     * @param array $modelId Model Id when multiple models are passed
     */
    public function addExport($data, $modelId = false)
    {

        $this->files   = $this->getFiles();
        $this->data    = $data;
        $this->modelId = $modelId;

        if ($model = $this->getModel()) {
            $totalRows  = $this->getModelCount();
            $this->addFile();
            $this->addHeader($this->tempFilename . $this->fileExtension);
            $currentRow = 0;
            do {
                $filter['limit'] = array($this->rowsPerBatch, $currentRow);
                if ($this->batch) {
                    $this->batch->addTask('Export_ExportCommand', $data['type'], 'addRows', $data, $modelId, $this->tempFilename, $filter);
                } else {
                    $this->addRows($data, $modelId, $this->tempFilename, $filter);
                }
                $currentRow = $currentRow + $this->rowsPerBatch;
            } while ($currentRow < $totalRows);

            if ($this->batch) {
                $this->batch->addTask('Export_ExportCommand', $data['type'], 'addFooter', $this->tempFilename . $this->fileExtension);
                $this->batch->setSessionVariable('files', $this->files);
            } else {
                $this->addFooter($this->tempFilename . $this->fileExtension);
                $this->_session        = new \Zend_Session_Namespace(__CLASS__);
                $this->_session->files = $this->files;
            }
        }
    }

    /**
     * Creates a new file and adds it to the files array
     */
    protected function addFile()
    {
        $exportTempDir = GEMS_ROOT_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

        if (! is_dir($exportTempDir)) {
            $oldmask = umask(0777);
            if (! @mkdir($exportTempDir, 0777, true)) {
                $this->throwLastError(sprintf($this->translate->_("Could not create '%s' directory."), $exportTempDir));
            }
            umask($oldmask);
        }

        $tempFilename       = $exportTempDir . 'export-' . md5(time() . rand());
        $this->tempFilename = $tempFilename;
        $basename           = $this->cleanupName($this->model->getName());
        $filename           = $basename;
        $i                  = 1;
        while (isset($this->files[$filename . $this->fileExtension])) {
            $filename = $basename . '_' . $i;
            $i++;
        }
        $this->filename = $filename;

        $this->files[$filename . $this->fileExtension] = $tempFilename . $this->fileExtension;

        $file = fopen($tempFilename . $this->fileExtension, 'w');

        fclose($file);
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    abstract protected function addHeader($filename);

    /**
     * Add model rows to file. Can be batched
     * @param array $data                       Data submitted by export form
     * @param array $modelId                    Model Id when multiple models are passed
     * @param string $tempFilename              The temporary filename while the file is being written
     * @param array  $filter                    Filter (limit) to use
     */
    public function addRows($data, $modelId, $tempFilename, $filter)
    {
        $this->data    = $data;
        $this->modelId = $modelId;
        $this->model   = $this->getModel();

        $this->model->setFilter($filter + $this->model->getFilter());
        if ($this->model) {
            $rows = $this->model->load();
            $file = fopen($tempFilename . $this->fileExtension, 'a');
            foreach ($rows as $row) {
                $this->addRow($row, $file);
            }
            fclose($file);
        }
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    abstract public function addRow($row, $file);

    /**
     * Add a footer to a specific file
     * @param string $filename The temporary filename while the file is being written
     */
    public function addFooter($filename) {}
        
    /**
     * Clean a proposed filename up so it can be used correctly as a filename
     * @param  string $filename Proposed filename
     * @return string           filtered filename
     */
    protected function cleanupName($filename)
    {
        $filename = str_replace(array('/', '\\', ':', ' '), '_', $filename);
        // Remove dot if it starts with one
        $filename = trim($filename, '.');

        return \MUtil_File::cleanupName($filename);
    }

    protected function filterDateFormat($value, $dateFormat, $columnName)
    {
        $storageFormat = $this->model->get($columnName, 'storageFormat');

        return \MUtil_Date::format($value, $dateFormat, $storageFormat);
    }

    protected function filterFormatFunction($value, $functionName)
    {
        if (!is_array($functionName) && method_exists($this, $functionName)) {
            return call_user_func(array($this, $functionName), $value);
        } else {
            return call_user_func($functionName, $value);
        }
    }    

    protected function filterHtml($result)
    {
        if ($result instanceof \MUtil_Html_ElementInterface && !($result instanceof \MUtil_Html_Sequence)) {
            if ($result instanceof \MUtil_Html_AElement) {
                $href   = $result->href;
                $result = $href;
            } elseif ($result->count() > 0) {
                $result = $result[0];
            }
        }

        if (is_object($result)) {
            // If it is Lazy, execute it
            if ($result instanceof \MUtil_Lazy_LazyInterface) {
                $result = \MUtil_Lazy::rise($result);
            }

            // If it is Html, render it
            if ($result instanceof \MUtil_Html_HtmlInterface) {
                $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
                if (null === $viewRenderer->view) {
                    $viewRenderer->initView();
                }
                $view = $viewRenderer->view;

                $result = $result->render($view);
            }
        }

        return $result;
    }    
    
    protected function filterItemDisplay($value, $functionName)
    {
        if (is_callable($functionName)) {
            $result = call_user_func($functionName, $value);
        } elseif (is_object($functionName)) {
            if (($functionName instanceof \MUtil_Html_ElementInterface) || method_exists($functionName, 'append')) {
                $object = clone $functionName;
                $result = $object->append($value);
            }
        } elseif (is_string($functionName)) {
            // Assume it is a html tag when a string
            $result = \MUtil_Html::create($functionName, $value);
        }

        return $result;
    }

    protected function filterMultiOptions($result, $multiOptions)
    {
        if (is_array($multiOptions)) {
            /*
             *  Sometimes a field is an array and will be formatted later on using the
             *  formatFunction -> handle each element in the array.
             */
            if (is_array($result)) {
                foreach ($result as $key => $value) {
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

    /**
     * Filter the data in a row so that correct values are being used
     * @param  array $row a row in the model
     * @return array The filtered row
     */
    protected function filterRow($row)
    {
        $exportRow = array();
        foreach ($row as $columnName => $result) {
            if ($this->model->get($columnName, 'label')) {
                $options = $this->model->get($columnName, $this->modelFilterAttributes);


                foreach ($options as $optionName => $optionValue) {
                    switch ($optionName) {
                        case 'dateFormat':
                            // if there is a formatFunction skip the date formatting
                            if (array_key_exists('formatFunction', $options)) {
                                continue;
                            }

                            $result = $this->filterDateFormat($result, $optionValue, $columnName);

                            break;
                        case 'formatFunction':
                            $result = $this->filterFormatFunction($result, $optionValue);

                            break;
                        case 'itemDisplay':
                            $result = $this->filterItemDisplay($result, $optionValue);

                            break;
                        case 'multiOptions':
                            $result = $this->filterMultiOptions($result, $optionValue);

                            break;
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

    /**
     * Finalizes the files stored in $this->files.
     * If it has 1 file, it will return that file, if it has more, it will return a zip containing all the files, named as the first file in the array.
     * @return File with download headers
     */
    public function finalizeFiles()
    {
        $this->getFiles();
        if (count($this->files) === 0) {
            return false;
        }
        $firstName = key($this->files);
        $file      = array();

        if (count($this->files) === 1) {
            $firstFile = $this->files[$firstName];

            $file['file']      = $firstFile;
            $file['headers'][] = "Content-Type: application/download";
            $file['headers'][] = "Content-Disposition: attachment; filename=\"" . $firstName . "\"";
        } elseif (count($this->files) >= 1) {
            $nameArray = explode('.', $firstName);
            array_pop($nameArray);
            $filename  = join('.', $nameArray) . '.zip';
            $zipFile   = dirname($this->files[$firstName]) . '/export-' . md5(time() . rand()) . '.zip';

            $zipArchive = new \ZipArchive();
            $zipArchive->open($zipFile, \ZipArchive::CREATE);

            foreach ($this->files as $newName => $tempName) {
                $zipArchive->addFile($tempName, $newName);
            }
            $zipArchive->close();

            foreach ($this->files as $tempName) {
                if (file_exists($tempName)) {
                    unlink($tempName);
                }
            }

            $file              = array();
            $file['file']      = $zipFile;
            $file['headers'][] = "Content-Type: application/download";
            $file['headers'][] = "Content-Disposition: attachment; filename=\"" . $filename . "\"";
        }

        $file['headers'][] = "Expires: Mon, 26 Jul 1997 05:00:00 GMT";    // Date in the past
        $file['headers'][] = "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT";
        $file['headers'][] = "Cache-Control: must-revalidate, post-check=0, pre-check=0";
        $file['headers'][] = "Pragma: cache";                          // HTTP/1.0

        if ($this->batch) {
            $this->batch->setSessionVariable('file', $file);
        } else {
            return $file;
        }
    }

    /**
     * Returns the files array. It might be stored in the batch session or normal session.
     * @return array Files array
     */
    protected function getFiles()
    {
        if (!$this->files) {
            $files = array();
            if ($this->batch) {
                $files = $this->batch->getSessionVariable('files');
            } else {
                $files = $this->_session->files;
            }
            if (!is_array($files)) {
                $files = array();
            }
            $this->files = $files;
        }
        return $this->files;
    }

    /**
     * Get the model to export
     * @return \MUtil_Model_ModelAbstract
     */
    protected function getModel()
    {
        $model = $this->batch->getVariable('model');
        if (is_array($model)) {
            if ($this->modelId && isset($model[$this->modelId])) {
                $model = $model[$this->modelId];
            } else {
                return false;
                //$model = false;
            }
        }

        $this->model = $model;

        if ($this->model->getMeta('exportPreprocess') === null) {
            $this->preprocessModel();
            $this->model->setMeta('exportPreprocess', true);
        }

        return $this->model;
    }

    /**
     * Get the number of items in a specific model, using the models paginator
     * @param  array $filter Filter for the model
     * @return int Number of items in the model
     */
    protected function getModelCount($filter = true)
    {
        if ($this->model && $this->model instanceof \MUtil_Model_ModelAbstract) {
            $totalCount = $this->model->loadPaginator()->getTotalItemCount();
            return $totalCount;
        }
        return 0;
    }

    /**
     * Preprocess the model to add specific options
     */
    protected function preprocessModel()
    {
        $this->getLabeledColumns();
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
