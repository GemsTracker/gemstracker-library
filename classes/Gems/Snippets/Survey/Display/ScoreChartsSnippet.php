<?php
/**
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Survey\Display;

/**
 * Show a chart for each 'score' element in a survey
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class ScoreChartsSnippet extends \Gems\Snippets\Tracker\Answers\TrackAnswersModelSnippet  {

    /**
     * Copied from parent, but insert chart instead of table after commented out part
     *
     * @param \Zend_View_Abstract $view
     * @return type
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $snippets = array();

        $data  = $this->getModel()->load();
        $token = null;

        // Find the first token with answers
        foreach($data as $tokenData) {
            $token = $this->loader->getTracker()->getToken($tokenData)->refresh();
            $tokenAnswers = $token->getRawAnswers();
            if (!empty($tokenAnswers)) {
                break;
            }
        }

        // Some spacing with previous elements
        $snippets[] = \MUtil\Html::create()->p(\MUtil\Html::raw('&nbsp;'), array('style'=>'clear:both;'));

        $config = $this->getConfig($token);
        // Fallback to all score elements in one chart when no config found
        if (! is_array($config)) {
            $config = array();
            foreach ($tokenAnswers as $key => $value)
            {
                if (substr(strtolower($key),0,5) == 'score') {
                    $config[0]['question_code'][] = $key;
                }
            }
        }

        // Set the default options
        $defaultOptions = array(
            'data'=>$data,
            'showHeaders' => false,
            'showButtons' => false
            );

        // Add all configured charts
        foreach ($config as $chartOptions) {
            $chartOptions = $chartOptions + $defaultOptions;
            $snippets[] = $this->loader->getSnippetLoader($this)->getSnippet('Survey\\Display\\BarChartSnippet', $chartOptions);
        }

        // Clear the floats
        $snippets[] = \MUtil\Html::create()->p(array('class'=>'chartfooter'));

        return $snippets;
    }

    /**
     * Get config options for this token
     *
     * Order of reading is track/round, survey, survey code
     *
     * @param \Gems\Tracker\Token $token
     */
    public function getConfig($token)
    {
        if (! $token) {
            return false;
        }
        try {
            $trackId = $token->getTrackId();
            $roundId = $token->getRoundId();

            $db = \Zend_Db_Table::getDefaultAdapter();

            $select = $db->select()->from('gems__chart_config')
                    ->where('gcc_tid = ?', $trackId)
                    ->where('gcc_rid = ?', $roundId);

            if ($result = $select->query()->fetch()) {
                $config = \Zend_Json::decode($result['gcc_config']);
                return $config;
            }

            $surveyId = $token->getSurveyId();
            $select = $db->select()->from('gems__chart_config')
                ->where('gcc_sid = ?', $surveyId);

            if ($result = $select->query()->fetch()) {
                $config = \Zend_Json::decode($result['gcc_config']);
                return $config;
            }

            $surveyCode = $token->getSurvey()->getCode();
            $select = $db->select()->from('gems__chart_config')
                ->where('gcc_code = ?', $surveyCode);

            $config = $select->query()->fetch();
            if ($config !== false) {
                $config = \Zend_Json::decode($config['gcc_config']);
            }

            return $config;

        } catch (\Exception $exc) {
            // Just ignore...
        }

        // If all fails, we might be missing the config table
        return false;
    }
}