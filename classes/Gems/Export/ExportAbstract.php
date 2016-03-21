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
     * @return array Default values in form
     */
    public function getDefaultFormValues() {}

    /**
     * Add an export command with specific details. Can be batched.
     * @param array $data    Data submitted by export form
     * @param array $modelId Model Id when multiple models are passed
     */
    public function addExport($data, $modelId=false)
    {

        $this->files = $this->getFiles();
        $this->data = $data;
        $this->modelId = $modelId;

        if ($model = $this->getModel()) {
            $totalRows = $this->getModelCount();
            $this->addFile();
            $this->addHeader($this->tempFilename.$this->fileExtension);
            $currentRow = 0;
            do {
                $filter['limit']  = array($this->rowsPerBatch, $currentRow);
                if ($this->batch) {
                    $this->batch->addTask('Export_ExportCommand', $data['type'], 'addRows', $data, $modelId, $this->tempFilename);
                } else {
                    $this->addRows($data, $modelId, $this->tempFilename);
                }
                $currentRow = $currentRow + $this->rowsPerBatch;
            } while ($currentRow < $totalRows);

            if ($this->batch) {
                $this->batch->addTask('Export_ExportCommand', $data['type'], 'addFooter', $this->tempFilename.$this->fileExtension);
                $this->batch->setSessionVariable('files', $this->files);
            } else {
                $this->addFooter($this->tempFilename.$this->fileExtension);
                $this->_session = new \Zend_Session_Namespace(__CLASS__);
                $this->_session->files = $this->files;
            }
        }
    }

    /**
     * Creates a new file and adds it to the files array
     */
    protected function addFile()
    {
        $tempFilename = GEMS_ROOT_DIR . '/var/tmp/export-' . md5(time() . rand());
        $this->tempFilename = $tempFilename;
        $basename = $this->cleanupName($this->model->getName());
        $filename = $basename;
        $i=1;
        while (isset($this->files[$filename.$this->fileExtension])) {
            $filename = $basename . '_' . $i;
            $i++;
        }
        $this->filename = $filename;

        $this->files[$filename.$this->fileExtension] = $tempFilename . $this->fileExtension;

        \MUtil_Echo::track($this->files);

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
     */
    public function addRows($data, $modelId, $tempFilename)
    {
        $this->data = $data;
        $this->modelId = $modelId;
        $this->model = $this->getModel();

        if ($this->model) {
            $rows = $this->model->load();
            $file = fopen($tempFilename . $this->fileExtension, 'a');
            foreach($rows as $row) {
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
                            $multiOptions = $optionValue;
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
                            break;

                        case 'formatFunction':
                            $callback = $optionValue;
                            if (!is_array($callback) && method_exists($this, $callback)) {
                                $result = call_user_func(array($this, $callback), $result);
                            } else {
                                $result = call_user_func($callback, $result);
                            }
                            break;

                        case 'dateFormat':
                            if (array_key_exists('formatFunction', $options)) {
                                // if there is a formatFunction skip the date formatting
                                continue;
                            }

                            $dateFormat = $optionValue;
                            $storageFormat = $this->model->get($columnName, 'storageFormat');
                            $result = \MUtil_Date::format($result, $dateFormat, $storageFormat);
                            break;

                        case 'itemDisplay':
                            $function = $optionValue;
                            if (is_callable($function)) {
                                $result = call_user_func($function, $result);
                            } elseif (is_object($function)) {
                                if (($function instanceof \MUtil_Html_ElementInterface)
                                    || method_exists($function, 'append')) {
                                    $object = clone $function;
                                    $result = $object->append($result);
                                }
                            } elseif (is_string($function)) {
                                // Assume it is a html tag when a string
                                $result = \MUtil_Html::create($function, $result);
                            }

                        default:
                            break;
                    }
                }
                if ($result instanceof \MUtil_Html_ElementInterface) {
                    if ($result->count() > 0) {
                        $result = $result[0];
                    } elseif ($result instanceof \MUtil_Html_AElement) {
                        $href = $result->href;
                        $result = $href[0];
                    }
                }

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
        $file = array();

        if (count($this->files) === 1) {
            $firstFile = $this->files[$firstName];

            $file['file']      = $firstFile;
            $file['headers'][] = "Content-Type: application/download";
            $file['headers'][] = "Content-Disposition: attachment; filename=\"" . $firstName . "\"";


        } elseif (count($this->files) >= 1) {
            $nameArray = explode('.', $firstName);
            array_pop($nameArray);
            $filename = join('.',$nameArray) . '.zip';
            $zipFile     = dirname($this->files[$firstName]) . '/export-' . md5(time() . rand()) . '.zip';

            $zipArchive = new \ZipArchive();
            $zipArchive->open(   $zipFile, \ZipArchive::CREATE);

            foreach($this->files as $newName => $tempName) {
                $zipArchive->addFile($tempName, $newName);
            }
            $zipArchive->close();

            foreach($this->files as $tempName) {
                if (file_exists($tempName)) {
                    unlink($tempName);
                }
            }

            $file = array();
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
                $model = false;
            }
        }

        return $this->model = $model;
    }

    /**
     * Get the number of items in a specific model, using the models paginator
     * @param  array $filter Filter for the model
     * @return int Number of items in the model
     */
    protected function getModelCount($filter=true)
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