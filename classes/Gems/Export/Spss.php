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
 * Short description for Spss
 *
 * Long description for class Spss (if any)...
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export_Spss extends Gems_Export_ExportAbstract
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
     * Set response headers and clean output
     *
     * @param string $filename
     * @return Zend_Controller_Response_Abstract
     */
    private function _doHeaders($filename)
    {
        $controller = $this->export->controller;
        $controller->getHelper('layout')->disableLayout();
        $controller->getHelper('viewRenderer')->setNoRender(true);
        $response   = $controller->getResponse();
        $response->clearAllHeaders();

        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true)
            ->setHeader('Content-type', 'text/plain; charset=UTF-8', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Pragma', 'public', true);

        return $response;
    }

    public function getDefaults()
    {
        return array('file' => 'data');
    }

    public function getFormElements(&$form, &$data)
    {
        $element = new Zend_Form_Element_Radio('file');
        $element->setLabel($this->_('Which file'));
        $element->setMultiOptions(array(
            'syntax'    => $this->_('syntax'),
            'data'      => $this->_('data')));
        $elements[] = $element;

        $element    = new MUtil_Form_Element_Exhibitor('help');
        $element->setValue($this->_('You need both a syntax and a data file. After downloading, open the syntax file in SPSS and modify the line /FILE="filename.dat" to include the full path to your data file. Then choose Run -> All to execute the syntax.'));
        $elements[] = $element;

        return $elements;
    }

    public function getName()
    {
        return 'spss';
    }

    /**
     * This method handles the export with the given options
     *
     * The method takes care of rendering the right script by using $this->export->controller to
     * access the controller object.
     *
     * @param array                     $data        The formdata
     * @param Gems_Tracker_Survey       $survey      The survey object we are exporting
     * @param array                     $answers     The array of answers
     * @param MUtil_Model_ModelAbstract $answerModel The modified answermodel that includes info about extra attributes
     * @param string                    $language    The language used / to use for the export
     */
    public function handleExport($data, $survey, $answers, $answerModel, $language)
    {
        if (isset($data[$this->getName()])) {
            $options = $data[$this->getName()];
        } else {
            $options = array();
        }

        if (isset($options['file'])) {
            if ($options['file'] == 'syntax') {
                $this->handleExportSyntax($data, $survey, $answers, $answerModel, $language);
            } else {
                $this->handleExportData($data, $survey, $answers, $answerModel, $language);
            }
        }
    }

    /**
     * This method handles the export with the given options for a syntax file
     *
     * The method takes care of rendering the right script by using $this->export->controller to
     * access the controller object.
     *
     * @param array                     $data        The formdata
     * @param Gems_Tracker_Survey       $survey      The survey object we are exporting
     * @param array                     $answers     The array of answers
     * @param MUtil_Model_ModelAbstract $answerModel The modified answermodel that includes info about extra attributes
     * @param string                    $language    The language used / to use for the export
     */
    public function handeExportData($data, $survey, $answers, $answerModel, $language)
    {
        $filename = $survey->getName() . '.dat';
        $response = $this->_doHeaders($filename);

        //We should create a model with the transformations we need
        //think of date translations, numers and strings
        $answerRow = reset($answers);
        $spssModel = new Gems_Export_ExportModel();
        foreach ($answerRow as $key => $value) {
            $options = array();
            $type = $answerModel->get($key, 'type');
            switch ($type) {
                case MUtil_Model::TYPE_DATE:
                    $options['storageFormat'] = 'yyyy-MM-dd';
                    $options['dateFormat']    = 'yyyy-MM-dd';
                    break;

                case MUtil_Model::TYPE_DATETIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $options['dateFormat']    = 'dd-MM-yyyy HH:mm:ss';
                    break;

                case MUtil_Model::TYPE_TIME:
                    $options['storageFormat'] = 'HH:mm:ss';
                    $options['dateFormat']    = 'HH:mm:ss';
                    break;

                case MUtil_Model::TYPE_NUMERIC:
                    break;

                //When no type set... assume string
                case MUtil_Model::TYPE_STRING:
                default:
                    $type                      = MUtil_Model::TYPE_STRING;
                    $options['formatFunction'] = $this->formatString;
                    break;
            }
            $options['type']           = $type;
            $spssModel->set($key, $options);
        }
        //Now apply the model to the answers
        $answers                   = new Gems_FormattedData($answers, $spssModel);

        //And output the data
        foreach ($answers as $answerRow) {
            $resultRow = implode(',', $answerRow);
            $response->appendBody($resultRow . "\n");
        }
    }

    /**
     * This method handles the export with the given options for a syntax file
     *
     * The method takes care of rendering the right script by using $this->export->controller to
     * access the controller object.
     *
     * @param array                     $data        The formdata
     * @param Gems_Tracker_Survey       $survey      The survey object we are exporting
     * @param array                     $answers     The array of answers
     * @param MUtil_Model_ModelAbstract $answerModel The modified answermodel that includes info about extra attributes
     * @param string                    $language    The language used / to use for the export
     */
    protected function handleExportSyntax($data, $survey, $answers, $answerModel, $language)
    {
        $filename    = $survey->getName() . '.sps';
        $filenameDat = $survey->getName() . '.dat';
        $response    = $this->_doHeaders($filename);

        //first output our script
        $response->appendBody(
            "SET UNICODE=ON.
GET DATA
 /TYPE=TXT
 /FILE=\"" . $filenameDat . "\"
 /DELCASE=LINE
 /DELIMITERS=\",\"
 /QUALIFIER=\"'\"
 /ARRANGEMENT=DELIMITED
 /FIRSTCASE=1
 /IMPORTCASE=ALL
 /VARIABLES=");
        $answerRow  = reset($answers);
        $labels     = array();
        $types      = array();
        $fixedNames = array();
        $questions  = $survey->getQuestionList($language);
        foreach ($answerRow as $key => $value) {
            $fixedNames[$key] = $this->fixName($key);
            $options          = array();
            $type             = $answerModel->get($key, 'type');
            switch ($type) {
                case MUtil_Model::TYPE_DATE:
                    $type = 'SDATE10';
                    break;

                case MUtil_Model::TYPE_DATETIME:
                case MUtil_Model::TYPE_TIME:
                    $type = 'DATETIME23';
                    break;

                case MUtil_Model::TYPE_NUMERIC:
                    $defaultSize = $this->defaultNumericSize;
                    $type        = 'F';
                    break;

                //When no type set... assume string
                case MUtil_Model::TYPE_STRING:
                default:
                    $defaultSize = $this->defaultAlphaSize;
                    $type        = 'A';
                    break;
            }
            $types[$key] = $type;
            if ($type == 'A' || $type == 'F') {
                $size = $answerModel->get($key, 'maxlength');   // This comes from db when available
                if (is_null($size)) {
                    $size = $answerModel->get($key, 'size');    // This is the display width
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
            if (isset($questions[$key])) {
                $labels[$key] = $questions[$key];
            }
            $response->appendBody("\n " . $fixedNames[$key] . ' ' . $type);
        }
        $response->appendBody(".\nCACHE.\nEXECUTE.\n");
        $response->appendBody("\n*Define variable labels.\n");
        foreach ($labels as $key => $label) {
            $label = $this->formatString($label);
            $response->appendBody("VARIABLE LABELS " . $fixedNames[$key] . " " . $label . "." . "\n");
        }

        $response->appendBody("\n*Define value labels.\n");
        foreach ($answerRow as $key => $value) {
            if ($options = $answerModel->get($key, 'multiOptions')) {
                $response->appendBody('VALUE LABELS ' . $fixedNames[$key]);
                foreach ($options as $option => $label) {
                    $label = $this->formatString($label);
                    if ($types[$key] == 'F') {
                        //Numeric
                        $response->appendBody("\n" . $option . ' ' . $label);
                    } else {
                        //String
                        $response->appendBody("\n" . '"' . $option . '" ' . $label);
                    }
                }
                $response->appendBody(".\n\n");
            }
        }
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
        $output = "'" . $output . "'";
        return $output;
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
}