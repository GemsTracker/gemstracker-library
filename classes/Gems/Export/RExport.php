<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Export;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class RExport extends ExportAbstract
{
    /**
     * Delimiter used for the data export
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.csv';

    /**
     * @var array   Array with the filter options that should be used for this exporter
     */
    protected $modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
    
    /**
     * Add the help snippet
     * 
     * @return string
     */
    public function getHelpSnippet()
    {
        return 'Export\\ExportInformationR';
    }

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'R Export';
    }

    public function addFooter($filename, $modelId = null, $data = null)
    {
        parent::addFooter($filename, $modelId, $data);
        if ($model = $this->getModel()) {
            $this->addSyntaxFile($filename);            
        }
    }

    /**
     * Add headers to a specific file
     * @param  string $filename The temporary filename while the file is being written
     */
    protected function addHeader($filename)
    {
        $file = fopen($filename, 'w');
        //$bom = pack("CCC", 0xef, 0xbb, 0xbf);
        //fwrite($file, $bom);

        $name = $this->getName();
        
        $labels = $this->getLabeledColumns();
        
        fputcsv($file, $labels, $this->delimiter, '"');

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
            // When numeric, there could be a non numeric answer, just ignore empty values
            if ($type == \MUtil\Model::TYPE_NUMERIC && !empty($value) && !is_numeric($value)) {
                $this->model->set($name, 'type', \MUtil\Model::TYPE_STRING);
                $changed = true;
            }
        }
        fputcsv($file, $exportRow, $this->delimiter, '"');
        if ($changed) {
            if ($this->batch) {
                $this->batch->setVariable('model', $this->model);
            } else {
                $this->_session->model = $this->model;
            }
        }
    }

    /**
     * Creates a correct syntax file and adds it to the Files array
     */
    protected function addSyntaxFile($filename)
    {
        $model       = $this->model;
        $files       = $this->getFiles();
        $datFileName = array_search($filename, $files);
        $spsFileName = substr($datFileName, 0, -strlen($this->fileExtension)) . '.R';
        $tmpFileName = substr($filename, 0, -strlen($this->fileExtension)) . '.R';

        $this->files[$spsFileName] = $tmpFileName;
        if ($this->batch) {
                $this->batch->setSessionVariable('files', $this->files);
            } else {
                $this->_session->files = $this->files;
            }
        $file = fopen($tmpFileName, 'a');

        //first output our script
        fwrite($file,
            'data <- read.csv("' . $datFileName . '", quote="\'\\"", stringsAsFactors=FALSE, encoding="UTF-8")' . "\n\n");

        $labeledCols = $this->getLabeledColumns();
        $labels      = array();
        $types       = array();
        $fixedNames  = array();
        //$questions  = $survey->getQuestionList($language);
        foreach ($labeledCols as $idx => $colname) {

            $fixedNames[$colname] = $this->fixName($colname);
            $options          = array();
            $type             = $model->get($colname, 'type');
            switch ($type) {
                case \MUtil\Model::TYPE_DATE:
                    $type = 'character';
                    break;

                case \MUtil\Model::TYPE_DATETIME:
                    $type = 'character';
                    break;

                case \MUtil\Model::TYPE_TIME:
                    $type = 'character';
                    break;

                case \MUtil\Model::TYPE_NUMERIC:
                    $type        = 'numeric';
                    break;

                //When no type set... assume string
                case \MUtil\Model::TYPE_STRING:
                default:
                    $type        = 'character';
                    break;
            }
            $types[$colname] = $type;
            $ref             = 'data[,' . ($idx + 1) . ']';
            fwrite($file, $ref . ' <- as.' . $type . '(' . $ref . ')' . "\n");
        }
            
        fwrite($file, "\n#Define variable labels.\n");
        foreach ($labeledCols as $idx => $colname) {
            $label = $this->formatString($model->get($colname, 'label'));
            fwrite($file, sprintf(
                'attributes(data)$variable.labels[%s] <- "%s"' . "\n",
                $idx + 1, $label));
        }

        fwrite($file, "\n#Define value labels.\n");
        foreach ($labeledCols as $idx => $colname) {
            if ($options = $model->get($colname, 'multiOptions')) {
                $ref    = 'data[,' . ($idx + 1) . ']';
                $type   = $types[$colname];
                if ($type == 'numeric') {
                    $values = join(',', array_keys($options));
                } else {
                    $values = '"' . join('","', array_keys($options)) . '"';
                }
                $labels = '"' . join('","', $options) . '"';
                fwrite($file, sprintf(
                '%1$s <- factor(%1$s, levels=c(%2$s), labels=c(%3$s))' . "\n",
                $ref, $values, $labels));
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
     * Formatting of strings for R export. Enclose in double quotes and escape double quotes
     * by doubling
     *
     * Example:
     * This isn't hard "to" understand
     * ==>
     * "This isn't ""hard"" to understand"
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
        $output = str_replace(array('"', "\r", "\n"), array('""', ' ', ' '), $output);
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
                case \MUtil\Model::TYPE_DATE:
                    $options['dateFormat']    = 'Y-M-d';
                    break;

                case \MUtil\Model::TYPE_DATETIME:
                    $options['dateFormat']    = 'd-M-Y H:i:s';
                    break;

                case \MUtil\Model::TYPE_TIME:
                    $options['dateFormat']    = 'H:i:s';
                    break;

                case \MUtil\Model::TYPE_NUMERIC:
                    break;

                //When no type set... assume string
                case \MUtil\Model::TYPE_STRING:
                default:
                    $type                      = \MUtil\Model::TYPE_STRING;
                    $options['formatFunction'] = 'formatString';
                    break;
            }
            $options['type']           = $type;
            $this->model->set($columnName, $options);
        }
    }
}