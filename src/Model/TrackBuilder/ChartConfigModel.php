<?php

declare(strict_types=1);

namespace Gems\Model\TrackBuilder;

use Gems\Db\ResultFetcher;
use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Raw;
use Zalt\Model\Sql\SqlRunnerInterface;

class ChartConfigModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected Translated $translatedUtil,
        protected ResultFetcher $resultFetcher,
    ) {
        parent::__construct('gems__chart_config', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gcc');
    }

    public function applySettings(bool $detailed, ?int $trackId, string $action): void
    {
        $empty = $this->translatedUtil->getEmptyDropdownArray();

        $this->metaModel->resetOrder();

        $this->metaModel->set('gcc_tid', [
            'label' => $this->_('Track'),
            'multiOptions' => $empty + $this->resultFetcher->fetchPairs(
                    'SELECT gtr_id_track, gtr_track_name FROM gems__tracks ORDER BY gtr_track_name;'
                )
        ]);
        $this->metaModel->set('gcc_rid', [
            'label' => $this->_('Round'),
        ]);
        $this->metaModel->set('gcc_sid', [
            'label' => $this->_('Survey'),
            'multiOptions' => $empty + $this->resultFetcher->fetchPairs(
                    'SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys ORDER BY gsu_survey_name;'
                ),
        ]);
        $this->metaModel->set('gcc_code', [
            'label' => $this->_('Survey code')
        ]);
        $this->metaModel->set('gcc_description', [
            'label' => $this->_('Description')
        ]);

        $roundStatement = 'SELECT gro_id_round, concat_ws(" ", gro_id_order, gro_survey_name, gro_round_description) FROM gems__rounds ORDER BY gro_id_order;';
        if ($detailed) {
            if ($trackId !== null) {
                $roundStatement = 'SELECT gro_id_round, concat_ws(" ", gro_id_order, gro_survey_name, gro_round_description) FROM gems__rounds WHERE gro_id_track = ' . $trackId . ' ORDER BY gro_id_order;';
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
            $this->metaModel->set('gcc_config', [
                'label' => $this->_('Config'),
                'elementClass' => 'textArea',
                'default' => $default,
            ]);
            if ($action === 'show') {
                $this->metaModel->set('gcc_config', [
                    'formatFunction' => [$this, 'formatJsonPre']
                ]);
            }
            $this->metaModel->setOnLoad('gcc_config', [$this, 'formatJson']);
        }

        $this->metaModel->set('gcc_rid', [
            'multiOptions' => $empty + $this->resultFetcher->fetchPairs($roundStatement)
        ]);
    }

    public function formatJsonPre($json, $new = null, $name = null, $context = null)
    {
        return Raw::raw('<pre>' . $json . '</pre>');
    }

    public function formatJson($json, $new = null, $name = null, $context = null)
    {
        try {
            if ($result = \Zend_Json::decode($json)) {
                // To prevent multiple new lines, make compact json
                $json = \Zend_Json::encode($result);
            }
        } catch (\Exception $exc) {
            // Oops, not valid json...
            if (substr($json, 0, 7) !== 'INVALID') {
                $json = "INVALIDJSON\n" . $json;
            }
            return $json;
        }

        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '  ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {
            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else {
                if (($char == '}' || $char == ']') && $outOfQuotes) {
                    $result .= $newLine;
                    $pos--;
                    for ($j = 0; $j < $pos; $j++) {
                        $result .= $indentStr;
                    }
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

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }
}