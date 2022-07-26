<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <mmenno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 * Configuration for barchart snippets
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class ChartconfigAction extends \Gems\Controller\ModelSnippetActionAbstract {

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems\Util\Translated
     */
    public $translatedUtil;

    protected function createModel($detailed, $action)
    {
        $model = new \Gems\Model\JoinModel('chartconfig', 'gems__chart_config', 'gcc');

        $empty = $this->translatedUtil->getEmptyDropdownArray();

        $model->set('gcc_tid', 'label', $this->_('Track'), 'multiOptions', $empty + $this->db->fetchPairs('SELECT gtr_id_track, gtr_track_name FROM gems__tracks ORDER BY gtr_track_name;'), 'onchange', 'this.form.submit();');
        $model->set('gcc_rid', 'label', $this->_('Round'));
        $model->set('gcc_sid', 'label', $this->_('Survey'), 'multiOptions', $empty + $this->db->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys ORDER BY gsu_survey_name;'));
        $model->set('gcc_code', 'label', $this->_('Survey code'));
        $model->set('gcc_description', 'label', $this->_('Description'));

        $roundStatement = 'SELECT gro_id_round, concat_ws(" ", gro_id_order, gro_survey_name, gro_round_description) FROM gems__rounds ORDER BY gro_id_order;';
        if ($detailed) {
            if ($this->requestHelper->isPost()) {
                $data = $this->request->getParsedBody();
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
        return \MUtil\Html\Raw::raw('<pre>' .$json . '</pre>');
    }

    public function formatjson($json, $new = null, $name = null, $context = null)
    {
        try {
            if ($result = \Zend_Json::decode($json)) {
                // To prevent multiple new lines, make compact json
                $json = \Zend_Json::encode($result);
            }
        } catch (\Exception $exc) {
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
