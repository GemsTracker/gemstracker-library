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
class CsvExport extends ExportAbstract
{
    /**
     * Delimiter used for CSV export
     * @var string
     */
    protected $delimiter = ';';

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.csv';

    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'CsvExport';
    }

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil\Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(&$form, &$data)
    {
        $element = $form->createElement('multiCheckbox', 'format');
        $element->setLabel($this->_('CSV options'))
                ->setMultiOptions(array(
                    'addHeader' => $this->_('Add headers with column names'),
                    'formatVariable' => $this->_('Export labels instead of field names'),
                    'formatAnswer' => $this->_('Format answers')
                ))
                ->setBelongsTo($this->getName())
                ->setSeparator(' ');
        $elements['format'] = $element;

        $element = $form->createElement('select', 'delimiter');
        $element->setLabel($this->_('Delimiter'))
                ->setMultiOptions(array(',' => ',', ';' => ';'))
                ->setBelongsTo($this->getName());
        $elements['delimiter'] = $element;

        return $elements;
    }

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues()
    {
        return array('format'=>array('addHeader', 'formatVariable', 'formatAnswer'), 'delimiter' => ';');
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

        $name = $this->getName();
        if (isset($this->data[$name], $this->data[$name]['format'], $this->data[$name]['format'][0]) && in_array('addHeader', $this->data[$name]['format'])) {
            $labeledCols = $this->getLabeledColumns();
            $labels      = array();

            if (in_array('formatVariable', $this->data[$name]['format'])) {
                foreach($labeledCols as $columnName) {
                    $labels[] = $this->metaModel->get($columnName, 'label');
                }
            } else {
                $labels = $labeledCols;
            }

            if (isset($this->data[$name]) && isset($this->data[$name]['delimiter'])) {
                $this->delimiter = $this->data[$name]['delimiter'];
            }

            fputcsv($file, $labels, $this->delimiter, '"');
        }

        fclose($file);
    }

    /**
     * Add model rows to file. Can be batched
     * @param array $data                       Data submitted by export form
     * @param int|string|null $modelId                    Model Id when multiple models are passed
     * @param string $tempFilename              The temporary filename while the file is being written
     * @param array  $filter                    Filter (limit) to use
     */
    public function addRows($data, $modelId, $tempFilename, $filter)
    {
        $name = $this->getName();
        if (isset($data[$name]) && isset($data[$name]['delimiter'])) {
            $this->delimiter = $data[$name]['delimiter'];
        }

        $this->modelFilterAttributes = $this->defaultModelFilterAttributes;
        if (!(isset($data[$name], $data[$name]['format'], $data[$name]['format'][0]) && in_array('formatAnswer', $data[$name]['format']))) {
            $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
        }
        parent::addRows($data, $modelId, $tempFilename, $filter);
    }

    /**
     * Add a separate row to a file
     * @param array $row a row in the model
     * @param resource $file The already opened file
     */
    public function addRow($row, $file)
    {
        $exportRow   = $this->filterRow($row);
        $labeledCols = $this->getLabeledColumns();
        $exportRow   = array_replace(array_flip($labeledCols), $exportRow);
        fputcsv($file, $exportRow, $this->delimiter, '"');
    }

    /**
     * Formatting of strings for CSV export.
     *
     * @param string $input
     * @return string
     */
    public function formatString($input)
    {
        if (is_array($input)) {
            $input = join(', ', $input);
        }
        if (('<=' == $input) || ('<' == $input)) {
            // Keeps < and <= values
            $output = $input;
        } else {
            $output = strip_tags($input ?? '');
        }
        $output = str_replace(array("\r", "\n"), array(' ', ' '), $output);
        
        $output = $this->filterCsvInjection($output);
        
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
            $type = $this->metaModel->get($columnName, 'type');
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
            $this->metaModel->set($columnName, $options);
        }
    }
}