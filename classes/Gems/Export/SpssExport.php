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
class SpssExport extends ExportAbstract
{

    /**
     * When no other size available in the answermodel, this will be used
     * for the size of alphanumeric types
     *
     * @var int
     */
    public $defaultAlphaSize   = 64;

    /**
     * When no other size available in the answermodel, this will be used
     * for the size of numeric types
     *
     * @var int
     */
    public $defaultNumericSize = 5;

    /**
     * Delimiter used for the DAT export
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.dat';

    /**
     * @var array   Array with the filter options that should be used for this exporter
     */
    protected $modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'SPSS Export';
    }

    public function addFooter($filename)
    {
        parent::addFooter($filename);
        if ($model = $this->getModel()) {
            $this->addSpssFile($filename);
        }
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    protected function addHeader($filename)
    {
        $file = fopen($filename, 'w');
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        fwrite($file, $bom);
        fclose($file);
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param file $file The already opened file
     */
    public function addRow($row, $file)
    {
        $exportRow = $this->filterRow($row);
        $labeledCols = $this->getLabeledColumns();
        $exportRow = array_replace(array_flip($labeledCols), $exportRow);
        $changed = false;
        foreach ($exportRow as $name => $value) {
            $type = $this->model->get($name, 'type');
            if ($type == \MUtil_Model::TYPE_NUMERIC && !is_numeric($value)) {
                $this->model->set($name, 'type', \MUtil_Model::TYPE_STRING);
                $changed = true;
            }
            
            if ($type == \MUtil_Model::TYPE_STRING) {
                $size = (int) $this->model->get($name, 'maxlength');
                if (mb_strlen($value)>$size) {
                    $this->model->set($name, 'maxlength', mb_strlen($value));
                    $changed = true;
                }
            }
        }
        fputcsv($file, $exportRow, $this->delimiter, "'");
        if ($changed) {
            $this->batch->setVariable('model', $this->model);
        }
    }

    /**
     * Creates a correct SPSS file and adds it to the Files array
     */
    protected function addSpssFile($filename)
    {
        $model       = $this->model;
        $files       = $this->getFiles();
        $datFileName = array_search($filename, $files);
        $spsFileName = substr($datFileName, 0, -strlen($this->fileExtension)) . '.sps';
        $tmpFileName = substr($filename, 0, -strlen($this->fileExtension)) . '.sps';

        $this->files[$spsFileName] = $tmpFileName;
        $this->batch->setSessionVariable('files', $this->files);
        $this->addHeader($tmpFileName);
        $file = fopen($tmpFileName, 'a');

        //first output our script
        fwrite($file,
            "SET UNICODE=ON.
SHOW LOCALE.
PRESERVE LOCALE.
SET LOCALE='en_UK'.

GET DATA
 /TYPE=TXT
 /FILE=\"" . $datFileName . "\"
 /DELCASE=LINE
 /DELIMITERS=\"".$this->delimiter."\"
 /QUALIFIER=\"'\"
 /ARRANGEMENT=DELIMITED
 /FIRSTCASE=1
 /IMPORTCASE=ALL
 /VARIABLES=");


        $labeledCols = $this->getLabeledColumns();
        $labels      = array();
        $types       = array();
        $fixedNames  = array();
        //$questions  = $survey->getQuestionList($language);
        foreach ($labeledCols as $colname) {

            $fixedNames[$colname] = $this->fixName($colname);
            $options          = array();
            $type             = $model->get($colname, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $type = 'SDATE10';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $type = 'DATETIME23';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $type = 'TIME8.0';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    $defaultSize = $this->defaultNumericSize;
                    $type        = 'F';
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:
                    $defaultSize = $this->defaultAlphaSize;
                    $type        = 'A';
                    break;
            }
            $types[$colname] = $type;
            if ($type == 'A' || $type == 'F') {
                $size = $model->get($colname, 'maxlength');   // This comes from db when available
                if (is_null($size)) {
                    $size = $model->get($colname, 'size');    // This is the display width
                    if (is_null($size)) {
                        $size = $defaultSize;                   // We just don't know, make it the default
                    }
                }
                if ($type == 'A') {
                    $type = $type . $size;
                } else {
                    $type = $type . $size . '.' . ($size - 1);    //decimal
                }
            }
            //if (isset($questions[$colname])) {
            //    $labels[$colname] = $questions[$colname];
            //}
            fwrite($file, "\n " . $fixedNames[$colname] . ' ' . $type);
        }
        fwrite($file, ".\nCACHE.\nEXECUTE.\n");
        fwrite($file, "\n*Define variable labels.\n");
        foreach ($labeledCols as $colname) {

            $label = "'" . $this->formatString($model->get($colname, 'label')) . "'";
            fwrite($file, "VARIABLE LABELS " . $fixedNames[$colname] . " " . $label . "." . "\n");
        }

        fwrite($file, "\n*Define value labels.\n");
        foreach ($labeledCols as $colname) {
            if ($options = $model->get($colname, 'multiOptions')) {
                fwrite($file, 'VALUE LABELS ' . $fixedNames[$colname]);
                foreach ($options as $option => $label) {
                    $label = "'" . $this->formatString($label) . "'";
                    if ($option !== "") {
                        if ($types[$colname] == 'F') {
                            //Numeric
                            fwrite($file, "\n" . $option . ' ' . $label);
                        } else {
                            //String
                            fwrite($file, "\n" . '"' . $option . '" ' . $label);
                        }
                    }
                }
                fwrite($file, ".\n\n");
            }
        }

        fwrite($file, "RESTORE LOCALE.\n");

        fclose($file);
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
        if (!preg_match("/^([a-z]|[A-Z])+.*$/", $input)) {
            $input = "q_" . $input;
        }
        $input = str_replace(array(" ", "-", ":", ";", "!", "/", "\\", "'"), array("_", "_hyph_", "_dd_", "_dc_", "_excl_", "_fs_", "_bs_", '_qu_'), $input);
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
        if (is_array($input)) {
            $input = join(', ', $input);
        }
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
                    $options['dateFormat']    = 'dd-MM-yyyy HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $options['dateFormat']    = 'HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
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
        }
    }
}