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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class Gems_Export_ExportAbstract extends \MUtil_Translate_TranslateableAbstract
{

    protected $batch;

    protected $data;

    protected $defaultExportModelSource = 'DefaultExportModelSource';

    protected $exportModelSource;

    protected $modelSourceName;

    protected $tempFilename;

    protected $files;

    protected $firstRow;

    protected $modelFilterAttributes = array('multiOptions', 'formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');

    public $loader;

    protected $model;

    protected $fileExtension;

    protected $rowsPerBatch = 500;

    protected $session;

    /**
     * return name of the specific export
     */
    abstract public function getName();

    /**
     * form elements for extra options for this particular export option
     */
    public function getFormElements(&$form, &$data) {}

    public function getDefaultFormValues() {}

    /**
     * Add an export command with specific details
     */
    public function addExport($exportModelSourceName, $filter, $data) {
        
        $this->files = $this->getFiles();
        $this->filter = $filter;
        $this->data = $data;

        $this->modelSourceName = $exportModelSourceName;
        if ($model = $this->getModel()) {
    
            // check if there are items in the model to export
            if (! $this->firstRow = $model->loadFirst($filter)) {
                return;
            } else {
                
                $totalRows = $this->getModelCount($filter);

                $this->addFile();

                $this->addHeader($this->tempFilename.$this->fileExtension);

                $currentRow = 0;
                do {
                    $filter['limit']  = array($this->rowsPerBatch, $currentRow);
                    if ($this->batch) {
                        $this->batch->addTask('Export_ExportCommand', $data['type'], 'addRows', $exportModelSourceName, $filter, $data, $this->tempFilename);
                    } else {
                        $this->addRows($exportModelSourceName, $filter, $data, $this->tempFilename);
                    }
                    $currentRow = $currentRow + $this->rowsPerBatch;
                } while ($currentRow < $totalRows);

                if ($this->batch) {
                    $this->batch->addTask('Export_ExportCommand', $data['type'], 'addFooter', $this->tempFilename.$this->fileExtension);
                    $this->batch->setSessionVariable('files', $this->files);
                } else {
                    $this->addFooter($this->tempFilename.$this->fileExtension);
                    $this->session = new \Zend_Session_Namespace(__CLASS__);
                    $this->session->files = $this->files;
                }
            }            
        }   
    }

    protected function addFile()
    {
        $tempFilename = GEMS_ROOT_DIR . '/var/tmp/export-' . md5(time() . rand());
        \MUtil_File::ensureDir(dirname($tempFilename));

        $this->tempFilename = $tempFilename;

        if ($basename = $this->getExportModelSource()->getName($this->filter)) {
            $basename = $this->cleanupName($basename);
        } else {
            $basename = $this->cleanupName($this->model->getName());
        }
        $filename = $basename;
        $i=1;
        while (isset($this->files[$filename.$this->fileExtension])) {
            $filename = $basename . '_' . $i;
            $i++;
        }
        $this->filename = $filename;
        
        $this->files[$filename.$this->fileExtension] = $tempFilename . $this->fileExtension;

        $file = fopen($tempFilename . $this->fileExtension, 'w');
        fclose($file);
    }


    /**
     * Add headers to a specific file
     */
    abstract protected function addheader($filename);

    public function addRows($exportModelSourceName, $filter, $data, $tempFilename)
    {
        $this->filter = $filter;
        $this->data = $data;
        $this->modelSourceName = $exportModelSourceName;
        $this->model = $this->getModel($this->modelSourceName);

        $rows = $this->model->load($filter);
        $file = fopen($tempFilename . $this->fileExtension, 'a');
        foreach($rows as $row) {
            $this->addRow($row, $file);
        }
        fclose($file);
    }

    abstract public function addRow($row, $file);

    public function addFooter($filename) {}

    protected function cleanupName($filename)
    {
        $filename = str_replace(array('/', '\\', ':', ' '), '_', $filename);
        // Remove dot if it starts with one
        $filename = trim($filename, '.');

        return \MUtil_File::cleanupName($filename);
    }

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
                            $result = MUtil_Date::format($result, $dateFormat, $storageFormat);
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
                $exportRow[$columnName] = $result;
            }
        }
        return $exportRow;
    }

    public function finalizeFiles()
    {
        MUtil_Echo::track('finalizing');
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

            $zipArchive = new ZipArchive();
            $zipArchive->open(   $zipFile, ZipArchive::CREATE);

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

    protected function getExportModelSource()
    {
        if (!$this->exportModelSource && $this->modelSourceName) {
            $this->exportModelSource = $this->loader->getExportModelSource($this->modelSourceName);
        }

        return $this->exportModelSource;
    }

    protected function getFiles()
    {
        if (!$this->files) {
            $files = array();
            if ($this->batch) {
                $files = $this->batch->getSessionVariable('files');
            } else {
                $files = $this->session->files;
            }
            if (!is_array($files)) {
                $files = array();
            }
            $this->files = $files;
        }
        return $this->files;
    }

    protected function getModel()
    {

        $this->model = false;
        if ($this->batch && $models = $this->batch->getSessionVariable('models')) {
            $this->model = $models[0];
            $this->preprocessModel();
            $this->modelSourceName = $this->defaultExportModelSource;
        } elseif ($this->modelSourceName instanceof \MUtil_Model_ModelAbstract) {
            $this->model = $this->modelSourceName;
            $this->modelSourceName = $this->defaultExportModelSource;
        }

        $exportModelSource = $this->getExportModelSource($this->modelSourceName);
        if (!$this->model) {
            $this->model = $exportModelSource->getModel($this->filter, $this->data);
            $this->preprocessModel();
        }

        return $this->model;
    }

    protected function getModelCount($filter=true)
    {
        if ($this->model && $this->model instanceof \MUtil_Model_ModelAbstract) {
            $totalCount = $this->model->loadPaginator($filter)->getTotalItemCount();
            return $totalCount;
        }
        return 0;
    }

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