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
abstract class ExportAbstract extends \MUtil\Translate\TranslateableAbstract implements ExportInterface
{
    /**
     * @var \Zend_Session_Namespace    Own session used for non-batch exports
     */
    protected $_session;

    /**
     * @var \Gems\Task\TaskRunnerBatch   The batch object if one is set
     */
    protected $batch;

    /**
     * @var array   Data submitted by export form
     */
    protected $data;

    /**
     * @var array   Array with the filter options that should be used for this exporter
     */
    protected $defaultModelFilterAttributes = ['multiOptions', 'formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay'];

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
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * @var \MUtil\Model\ModelAbstract  Current model to export
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
     * @param  \MUtil\Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(&$form, &$data) {}

    /**
     * @return string|null Optional snippet containing help text
     */
    public function getHelpSnippet()
    {
        return null;
    }

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
    public function getDefaultFormValues() {
        return [];
    }

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
     * Creates a new file and adds it to the files array
     */
    protected function addFile()
    {
        $exportTempDir = $this->getExportTempDir();

        if (! is_dir($exportTempDir)) {
            \MUtil\File::ensureDir($exportTempDir);
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

    public function afterRegistry() {
        parent::afterRegistry();

        if (!$this->batch) {
            $this->_session = new \Zend_Session_Namespace(__CLASS__);
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
    /**
     * Add a footer to a specific file
     * @param string $filename The temporary filename while the file is being written
     * @param string $modelId ID of the current model
     * @param array $data Current export settings
     */
    public function addFooter($filename, $modelId = null, $data = null) {
        $this->modelId = $modelId;
    }

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

        return \MUtil\File::cleanupName($filename);
    }

    /**
     * Single point for mitigating csv injection vulnerabilities
     *
     * https://www.owasp.org/index.php/CSV_Injection
     *
     * @param string $input
     * @return string
     */
    protected function filterCsvInjection($input)
    {
        // Try to prevent csv injection
        $dangers = ['=', '+', '-', '@'];

        // Trim leading spaces for our test
        $trimmed = trim($input);

        if (strlen($trimmed)>1 && in_array($trimmed[0], $dangers)) {
            return "'" . $input;
        }  else {
            return $input;
        }
    }

    protected function filterDateFormat($value, $dateFormat, $columnName)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($dateFormat);
        }
        
        $storageFormat = $this->model->get($columnName, 'storageFormat');
        return \MUtil\Model::reformatDate($value, $storageFormat, $dateFormat);
    }

    protected function filterFormatFunction($value, $functionName)
    {
        if (is_string($functionName) && method_exists($this, $functionName)) {
            return call_user_func(array($this, $functionName), $value);
        } else {
            return call_user_func($functionName, $value);
        }
    }

    protected function filterHtml($result)
    {
        if ($result instanceof \MUtil\Html\ElementInterface && !($result instanceof \MUtil\Html\Sequence)) {
            if ($result instanceof \MUtil\Html\AElement) {
                $href   = $result->href;
                $result = $href;
            } elseif ($result->count() > 0) {
                $result = $result[0];
            }
        }

        if (is_object($result)) {
            // If it is Lazy, execute it
            if ($result instanceof \MUtil\Lazy\LazyInterface) {
                $result = \MUtil\Lazy::rise($result);
            }

            // If it is Html, render it
            if ($result instanceof \MUtil\Html\HtmlInterface) {
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
            if (($functionName instanceof \MUtil\Html\ElementInterface) || method_exists($functionName, 'append')) {
                $object = clone $functionName;
                $result = $object->append($value);
            }
        } elseif (is_string($functionName)) {
            // Assume it is a html tag when a string
            $result = \MUtil\Html::create($functionName, $value);
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
            if (!is_null($this->model->get($columnName, 'label'))) {
                $options = $this->model->get($columnName, $this->modelFilterAttributes);


                foreach ($options as $optionName => $optionValue) {
                    switch ($optionName) {
                        case 'dateFormat':
                            // if there is a formatFunction skip the date formatting
                            if (array_key_exists('formatFunction', $options)) {
                                continue 2;
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

                if ($result instanceof \DateTimeInterface) {
                    $result = $this->filterDateFormat($result, 'Y-m-d H:i:s', $columnName);
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
     * @param array $data Current export settings
     * @return array File with download headers
     */
    public function finalizeFiles($data=null)
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
     * Return the answermodel for the given filter
     *
     * @param array $filter
     * @param array $data
     * @param array|string $sort
     * @return \MUtil\Model\ModelAbstract model
     */
    protected function getAnswerModel($exportModelSource, array $filter, array $data, $sort)
    {
        $exportModelSource = $this->loader->getExportModelSource($exportModelSource);
        $model = $exportModelSource->getModel($filter, $data);
        $noExportColumns = $model->getColNames('noExport');
        foreach($noExportColumns as $colName) {
            $model->remove($colName, 'label');
        }
        $model->applyParameters($filter, true);

        $model->addSort($sort);

        return $model;
    }

    protected function getExportTempDir()
    {
        return GEMS_ROOT_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
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
     * @return \MUtil\Model\ModelAbstract
     */
    public function getModel()
    {
        if ($this->batch) {
            $model = $this->batch->getVariable('model');
        } else {
            $model = $this->_session->model;
        }
        if (is_array($model)) {
            if ($this->modelId) {
                if (isset($model[$this->modelId]) && $model[$this->modelId] instanceof \MUtil\Model\ModelAbstract) {
                    $model = $model[$this->modelId];
                } else {
                    $modelType = null;
                    $filter = null;
                    $data = null;
                    $sort = null;
                    $extra = null;

                    if (isset($model[$this->modelId], $model[$this->modelId]['model'])) {
                        $modelType = $model[$this->modelId]['model'];
                    } elseif (isset($model['model'])) {
                        $modelType = $model['model'];
                    }
                    if (isset($model[$this->modelId], $model[$this->modelId]['filter'])) {
                        $filter = $model[$this->modelId]['filter'];
                    } elseif (isset($model['filter'])) {
                        $filter = $model['filter'];
                    }
                    if (isset($model[$this->modelId], $model[$this->modelId]['data'])) {
                        $data = $model[$this->modelId]['data'];
                    } elseif (isset($model['data'])) {
                        $data = $model['data'];
                    }
                    if (isset($model[$this->modelId], $model[$this->modelId]['sort'])) {
                        $sort = $model[$this->modelId]['sort'];
                    } elseif (isset($model['sort'])) {
                        $sort = $model['sort'];
                    }
                    if (isset($model[$this->modelId], $model[$this->modelId]['extra'])) {
                        $extra = $model[$this->modelId]['extra'];
                    } elseif (isset($model['extra'])) {
                        $extra = $model['extra'];
                    }

                    if ($modelType && is_callable($modelType)) {
                        $model = $modelType($this->loader, $filter, $data, $sort, $extra);
                    } else {
                        $exportModelSource = 'AnswerExportModelSource';
                        if (isset($model[$this->modelId]['exportModelSource'])) {
                            $exportModelSource = $model[$this->modelId]['exportModelSource'];
                        } elseif (isset($model['exportModelSource'])) {
                            $exportModelSource = $model['exportModelSource'];
                        }
                        $filter['gto_id_survey'] = $this->modelId;

                        $model = $this->getAnswerModel($exportModelSource, $filter, $data, $sort);
                    }
                }
            } else {
                return false;
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
        if ($this->model && $this->model instanceof \MUtil\Model\ModelAbstract) {
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
     * @param \Gems\Task\TaskRunnerBatch $batch
     */
    public function setBatch(\Gems\Task\TaskRunnerBatch $batch)
    {
        $this->batch = $batch;
    }

    /**
     * Set the model when not in batch mode
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    public function setModel(\MUtil\Model\ModelAbstract $model)
    {
        if ($this->_session) {
            $this->_session->model = $model;
        }
    }

}
