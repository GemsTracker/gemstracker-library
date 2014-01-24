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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Short description for Excel
 *
 * Long description for class Excel (if any)...
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export_Excel extends Gems_Export_ExportAbstract implements Gems_Export_ExportBatchInterface
{
    /**
     * Return an array of Form Elements for this specific export
     *
     * @param type $form
     * @param type $data
     * @return array of Zend_Form_Element
     */
    public function getFormElements(&$form, &$data)
    {
        $element = new Zend_Form_Element_MultiCheckbox('format');
        $element->setLabel($this->_('Excel options'))
            ->setMultiOptions(array(
                'formatVariable' => $this->_('Export questions instead of variable names'),
                'formatAnswer' => $this->_('Format answers')
            ));
        $elements[] = $element;

        return $elements;
    }

    /**
     * Sets the default form values when this export type is first chosen
     *
     * @return array
     */
    public function getDefaults()
    {
        return array('format'=>array('formatVariable', 'formatAnswer'));
    }

    /**
     * Returns the unique name for this class
     *
     * It will be used for handling this export's specific options
     *
     * @return string
     */
    public function getName()
    {
        return 'excel';
    }

    /**
     * This method handles the export with the given options
     *
     * The method takes care of rendering the right script by using $this->export->controller to
     * access the controller object.
     *
     * @param array               $data     The formdata
     * @param Gems_Tracker_Survey $survey   The survey object we are exporting
     * @param array               $answers  The array of answers
     */
    public function handleExport($data, $survey, $answers, $answerModel, $language)
    {
        // We only do batch export
        return;
    }

    /**
     * This method handles the export with the given options
     *
     * We open the file and add tasks to the batch to export in steps of 500 records.
     * This should be small enough to not run out of time/memory.
     *
     * We make use of the Export_ExportCommand to forward calls to this class.
     * Extra methods in this class are
     *      handleExportBatchStep Exports the records
     * and
     *      handleExportBatchFinalize Write the footer to the file
     *
     * @param Gems_Task_TaskRunnerBatch $batch       The batch to start
     * @param array                     $filter      The filter to use
     * @param string                    $language    The language used / to use for the export
     * @param array                     $data        The formdata
     */
    public function handleExportBatch($filter, $language, $data) {
        $batch = $this->_batch;
        $survey      = $this->loader->getTracker()->getSurvey($data['sid']);
        $answerCount = $survey->getRawTokenAnswerRowsCount($filter);
        $answers     = $survey->getRawTokenAnswerRows(array('limit'=>1,'offset'=>0) + $filter); // Limit to one response
        $filename    = $survey->getName() . '.xls';
        
        if (count($answers) === 0) {
            $noData = sprintf($this->_('No %s found.'), $this->_('data'));
            $answers = array($noData => $noData);
        } else {
            $answers = reset($answers);
        }

        $files['file']= 'export-' . md5(time() . rand());
        $files['headers'][] = "Content-Type: application/download";
        $files['headers'][] = "Content-Disposition: attachment; filename=\"" . $filename . "\"";
        $files['headers'][] = "Expires: Mon, 26 Jul 1997 05:00:00 GMT";    // Date in the past
        $files['headers'][] = "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT";
        $files['headers'][] = "Cache-Control: must-revalidate, post-check=0, pre-check=0";
        $files['headers'][] = "Pragma: cache";                          // HTTP/1.0

        $batch->setMessage('file', $files);
        $batch->setMessage('export-progress', $this->_('Initializing export'));

        $f = fopen(GEMS_ROOT_DIR . '/var/tmp/' . $files['file'], 'w');
        fwrite($f, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv=Content-Type content="text/html; charset=UTF-8">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 11">
<style>
    /* Default styles for tables */

    table {
        border-collapse: collapse;
        border: .5pt solid #000000;
    }

    tr th {
        font-weight: bold;
        padding: 3px 8px;
        border: .5pt solid #000000;
        background: #c0c0c0
    }
    tr td {
        padding: 3px 8px;
        border: .5pt solid #000000;
    }
    td {mso-number-format:"\@";}
</style>
</head>
<body>');

        if (isset($data[$this->getName()])) {
            $options = $data[$this->getName()];
            if (isset($options['format']))  {
                $options = $options['format'];
            }
        } else {
            $options = array();
        }

        $headers = array();
        if (in_array('formatVariable', $options)) {
            $questions = $survey->getQuestionList($language);
            //@@TODO This breaks date formatting, think of a way to fix this, check out the spss exports for
            //a more direct export, also check UTF-8 differences between view / direct output
            foreach ($answers as $key => $value) {
                if (isset($questions[$key])) {
                    $headers[$key] = $questions[$key];
                } else {
                    $headers[$key] = $key;
                }
            }
        } else {
            $headers = array_keys($answers);
        }

        //Only for the first row: output headers
        $output = "<table>\r\n";
        $output .= "\t<thead>\r\n";
        $output .= "\t\t<tr>\r\n";
        foreach ($headers as $name => $value) {
            $output .= "\t\t\t<th>$value</th>\r\n";
        }
        $output .= "\t\t</tr>\r\n";
        $output .= "\t</thead>\r\n";
        $output .= "\t<tbody>\r\n";

        fwrite($f, $output);

        fclose($f);
        // Add as many steps as needed
        $current = 0;
        $step = 500;
        do {
            $filter['limit']  = $step;
            $filter['offset'] = $current;
            $batch->addTask('Export_ExportCommand', $data['type'], 'handleExportBatchStep', $data, $filter, $language);
            $current = $current + $step;
        } while ($current < $answerCount);

        $batch->addTask('Export_ExportCommand', $data['type'], 'handleExportBatchFinalize');

        return;
    }

    public function handleExportBatchStep($data, $filter, $language)
    {
        $batch   = $this->_batch;
        $files   = $batch->getMessage('file', array());
        $survey  = $this->loader->getTracker()->getSurvey($data['sid']);
        $answers = $survey->getRawTokenAnswerRows($filter);
        $answerModel = $survey->getAnswerModel($language);
        //Now add the organization id => name mapping
        $answerModel->set('organizationid', 'multiOptions', $this->loader->getCurrentUser()->getAllowedOrganizations());
        $batch->setMessage('export-progress', sprintf($this->_('Exporting records %s and up'), $filter['offset']));

        if (isset($data[$this->getName()])) {
            $options = $data[$this->getName()];
            if (isset($options['format']))  {
                $options = $options['format'];
            }
        } else {
            $options = array();
        }

        if (in_array('formatAnswer', $options)) {
            $answers = new Gems_FormattedData($answers, $answerModel);
        }

        $f = fopen(GEMS_ROOT_DIR . '/var/tmp/' . $files['file'], 'a');
        if (! $f) {
            $edata = error_get_last();
            throw new Gems_Exception('Error opening ' . $files['file'] . '. ' . $edata['message']);
        }
        foreach($answers as $answer)
        {
            $output = "\t\t<tr>\r\n";
            foreach ($answer as $key => $value) {
                $output .= "\t\t\t<td>$value</td>\r\n";
            }
            $output .= "\t\t</tr>\r\n";
            fwrite($f, $output);
        }
        fclose($f);
    }

    public function handleExportBatchFinalize()
    {
        $files = $this->_batch->getMessage('file', array());
        $this->_batch->setMessage('export-progress', $this->_('Export finished'));
        $f = fopen(GEMS_ROOT_DIR . '/var/tmp/' . $files['file'], 'a');
        fwrite($f, '            </tbody>
        </table>
    </body>
</html>');
        fclose($f);
    }

}