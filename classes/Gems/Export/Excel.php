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
class Gems_Export_Excel extends Gems_Export_ExportAbstract
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
        $questions = $survey->getQuestionList($language);
        if (isset($data[$this->getName()])) {
            $options = $data[$this->getName()];
            if (isset($options['format']))  {
                $options = $options['format'];
            }
        } else {
            $options = array();
        }

        if (in_array('formatVariable', $options)) {
            //@@TODO This breaks date formatting, think of a way to fix this, check out the spss exports for
            //a more direct export, also check UTF-8 differences between view / direct output
            foreach ($answers[0] as $key => $value) {
                if (isset($questions[$key])) {
                    $headers[0][$key] = $questions[$key];
                } else {
                    $headers[0][$key] = $key;
                }
            }
        } else {
            $headers[0] = array_keys($answers[0]);
        }
        $answers = array_merge($headers, $answers);

        if (in_array('formatAnswer', $options)) {
            $answers = new Gems_FormattedData($answers, $answerModel);
        }

        $this->view->result = $answers;
        $this->view->filename = $survey->getName() . '.xls';
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts');
        $this->export->controller->render('excel',null,true);
    }
}