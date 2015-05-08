<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Default
 * @author     Menno Dekker <mmenno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Configuration for barchart snippets
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Default_ChartconfigAction extends \Gems_Controller_ModelSnippetActionAbstract {

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    protected function createModel($detailed, $action)
    {
        $model = new \Gems_Model_JoinModel('chartconfig', 'gems__chart_config', 'gcc');

        $empty = $this->loader->getUtil()->getTranslated()->getEmptyDropdownArray();

        $model->set('gcc_tid', 'label', $this->_('Track'), 'multiOptions', $empty + $this->db->fetchPairs('SELECT gtr_id_track, gtr_track_name FROM gems__tracks;'), 'onchange', 'this.form.submit();');
        $model->set('gcc_rid', 'label', $this->_('Round'));
        $model->set('gcc_sid', 'label', $this->_('Survey'), 'multiOptions', $empty + $this->db->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys;'));
        $model->set('gcc_code', 'label', $this->_('Survey code'));
        $model->set('gcc_description', 'label', $this->_('Description'));

        $roundStatement = 'SELECT gro_id_round, concat_ws(" ", gro_id_order, gro_survey_name, gro_round_description) FROM gems__rounds ORDER BY gro_id_order;';
        if ($detailed) {
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getParams();
                if (array_key_exists('gcc_tid', $data) && !empty($data['gcc_tid'])) {
                    $trackId = (int) $data['gcc_tid'];
                    $roundStatement = 'SELECT gro_id_round, concat_ws(" ", gro_id_order, gro_survey_name, gro_round_description) FROM gems__rounds WHERE gro_id_track = ' . $trackId . ' ORDER BY gro_id_order;';
                }
            }

            $default = '[
  {
    "question_code":[
      "SCORE1",
      "SCORE2"
    ],
    "question_text":"DSM scores",
    "grid":false,
    "min":25,
    "max":100,
    "rulers":[
      {
        "value":60,
        "class":"negative",
        "label":"lower"
      },
      {
        "value":69,
        "class":"positive",
        "label":"upper"
      }
    ]
  }
]';
            $model->set('gcc_config', 'label', $this->_('Config'), 'elementClass', 'textArea', 'default', $default);
            if ($action == 'show') {
                $model->set('gcc_config', 'formatFunction', array($this, 'formatjsonpre'));
            }
            $model->setOnLoad('gcc_config', array($this, 'formatjson'));
        }

        $model->set('gcc_rid', 'multiOptions', $empty + $this->db->fetchPairs($roundStatement));

        return $model;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('Chart config', 'Chart configs', $count);
    }

    public function formatjsonpre($json, $new = null, $name = null, $context = null)
    {
        return \MUtil_Html_Raw::raw('<pre>' .$json . '</pre>');
    }

    public function formatjson($json, $new = null, $name = null, $context = null)
    {
        try {
            if ($result = \Zend_Json::decode($json)) {
                // To prevent multiple new lines, make compact json
                $json = \Zend_Json::encode($result);
            }
        } catch (Exception $exc) {
            // Oops, not valid json...
            If (substr($json, 0,7) !== 'INVALID') {
                $json = "INVALIDJSON\n" . $json;
            }
            return $json;
        }

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++)
        {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++)
                {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {

                $result .= $newLine;

                if ($char == '{' || $char == '[') {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++)
                {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

}