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
class Gems_Export_SpssExport extends \Gems_Export_ExportAbstract
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

    protected $delimiter = ',';

    protected $fileExtension = '.dat';

    protected $modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');

    /**
     * return name of the specific export
     */
    public function getName() {
        return 'test Export';
    }

    /**
     * Add an export command with specific details
     */
    public function addExport($exportModelName, $filter, $data)
    {
        parent::addExport($exportModelName, $filter, $data);
        MUtil_Echo::track($this->filename);
        if ($model = $this->getModel()) {
            $this->addSpssFile();

            $this->batch->setSessionVariable('files', $this->files);
        }
    }

    /**
     * Add headers to a specific file
     */
    protected function addheader($filename)
    {
        $file = fopen($filename, 'w');
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        fwrite($file, $bom);
        fclose($file);
    }

    public function addRow($row, $file)
    {
        $exportRow = $this->filterRow($row);
        fputcsv($file, $exportRow, $this->delimiter, "'");
    }

    protected function addSpssFile()
    {
        MUtil_Echo::track($this->filename);
        $model = $this->getModel($this->modelSourceName);

        $this->files[$this->filename.'.sps'] = $this->tempFilename . '.sps';
        $this->addheader($this->tempFilename . '.sps');

        $file = fopen($this->tempFilename . '.sps', 'a');

        $filenameDat = $this->filename . $this->fileExtension;

        //first output our script
        fwrite($file,
            "SET UNICODE=ON.
GET DATA
 /TYPE=TXT
 /FILE=\"" . $filenameDat . "\"
 /DELCASE=LINE
 /DELIMITERS=\"".$this->delimiter."\"
 /QUALIFIER=\"'\"
 /ARRANGEMENT=DELIMITED
 /FIRSTCASE=1
 /IMPORTCASE=ALL
 /VARIABLES=");


        $labeledCols = $model->getColNames('label');
        $labels     = array();
        $types      = array();
        $fixedNames = array();
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
        $output = strip_tags($input);
        $output = str_replace(array("'", "\r", "\n"), array("''", ' ', ' '), $output);
        //$output = "'" . $output . "'";
        return $output;
    }

    protected function preprocessModel()
    {
        $labeledCols = $this->model->getColNames('label');
        foreach($labeledCols as $columnName) {
            $options = array();
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $options['storageFormat'] = 'yyyy-MM-dd';
                    $options['dateFormat']    = 'yyyy-MM-dd';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $options['dateFormat']    = 'dd-MM-yyyy HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
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