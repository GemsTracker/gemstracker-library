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
 * @subpackage Snippets\Survey\Display
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Show a chart for each 'score' element in a survey
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Survey_Display_ScoreChartsSnippet extends Gems_Snippets_Tracker_Answers_TrackAnswersModelSnippet  {

    /**
     * Copied from parent, but insert chart instead of table after commented out part
     *
     * @param \Zend_View_Abstract $view
     * @return type
     */
    public function getHtmlOutput(\Zend_View_Abstract $view) {
        $snippets = array();

        $data = $this->getModel()->load();

        // Find the first token with answers
        foreach($data as $tokenData) {
            $token = $this->loader->getTracker()->getToken($tokenData)->refresh();
            $tokenAnswers = $token->getRawAnswers();
            if (!empty($tokenAnswers)) {
                break;
            }
        }

        // Some spacing with previous elements
        $snippets[] = MUtil_Html::create()->p(MUtil_Html::raw('&nbsp;'), array('style'=>'clear:both;'));

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
            $snippets[] = $this->loader->getSnippetLoader($this)->getSnippet('Survey_Display_BarChartSnippet', $chartOptions);
        }

        // Clear the floats
        $snippets[] = MUtil_Html::create()->p(array('class'=>'chartfooter'));

        return $snippets;
    }

    /**
     * Get config options for this token
     *
     * Order of reading is track/round, survey, survey code
     *
     * @param Gems_Tracker_Token $token
     */
    public function getConfig($token)
    {
        try {
            $trackId = $token->getTrackId();
            $roundId = $token->getRoundId();

            $db = Zend_Db_Table::getDefaultAdapter();

            $select = $db->select()->from('gems__chart_config')
                    ->where('gcc_tid = ?', $trackId)
                    ->where('gcc_rid = ?', $roundId);

            if ($result = $select->query()->fetch()) {
                $config = Zend_Json::decode($result['gcc_config']);
                return $config;
            }

            $surveyId = $token->getSurveyId();
            $select = $db->select()->from('gems__chart_config')
                ->where('gcc_sid = ?', $surveyId);

            if ($result = $select->query()->fetch()) {
                $config = Zend_Json::decode($result['gcc_config']);
                return $config;
            }

            $surveyCode = $token->getSurvey()->getCode();
            $select = $db->select()->from('gems__chart_config')
                ->where('gcc_code = ?', $surveyCode);

            $config = $select->query()->fetch();
            if ($config !== false) {
                $config = Zend_Json::decode($config['gcc_config']);
            }

            return $config;

        } catch (Exception $exc) {
            // Just ignore...
        }

        // If all fails, we might be missing the config table
        return false;
    }
}