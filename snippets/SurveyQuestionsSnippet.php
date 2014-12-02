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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Shows the questions in a survey in a human readavle manner
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class SurveyQuestionsSnippet extends MUtil_Snippets_TableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table table-striped table-bordered table-hover table-condensed';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;
    
    public $showAnswersLimit      = 5;
    public $showAnswersNone       = 'n/a';
    public $showAnswersNoneEnd     = '</em>';
    public $showAnswersNoneStart   = '<em class="disabled">';
    public $showAnswersRemoved    = '&hellip;';
    public $showAnswersSeparator  = ' <span class="separator">|</span> ';
    // public $showAnswersSeparator  = ' <span class="separator">&bull;</span> ';
    // public $showAnswersSeparator  = ' <span class="separator">&ordm;</span> ';
    // public $showAnswersSeparator  = '<span class="separator">&#9002; &#9001;</span>';
    public $showAnswersSepEnd     = '';
    // public $showAnswersSepEnd     = '<span class="separator">&#9002;</span>';
    public $showAnswersSepStart   = '';
    // public $showAnswersSepStart   = '<span class="separator">&#9001;</span>';
    public $showAnswersTranslated = false;
    public $showAnswerTypeEnd     = '</em>';
    public $showAnswerTypeStart   = '<em>';

    /**
     * Required: the id of the survey to show
     *
     * @var int
     */
    public $surveyId;

    /**
     * Optional: alternative method for passing surveyId or trackId
     *
     * @var array
     */
    protected $trackData;

    /**
     * Optional, alternative way to get $trackId
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Alternative way to get surveyId: the id of the track whose first active round is shown
     *
     * @var int
     */
    public $trackId;

    /**
     * Add the columns ot the table
     *
     * This is a default implementation, overrule at will
     *
     * @param MUtil_Html_TableElement $table
     */
    protected function addColumns(MUtil_Html_TableElement $table)
    {
        $table->addColumn(array(MUtil_Html::raw($this->repeater->question), 'class' => $this->repeater->class), $this->_('Question'));
        $table->addColumn($this->repeater->answers->call($this, 'showAnswers', $this->repeater->answers), $this->_('Answer options'));
        // $table->addColumn($this->repeater->type, 'Type');
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        // Apply translations
        if (! $this->showAnswersTranslated) {
            // Here, not in e.g. __construct as these vars may be set during initiation
            $this->showAnswersNone       = $this->_($this->showAnswersNone);
            $this->showAnswersRemoved    = $this->_($this->showAnswersRemoved);
            $this->showAnswersSeparator  = $this->_($this->showAnswersSeparator);
            $this->showAnswersTranslated = true;
        }

        // Overrule any setting of these values from source
        $this->data = null;
        $this->repeater = null;

        if (! $this->surveyId) {
            if ($this->trackData && (! $this->trackId)) {
                // Look up key values from trackData
                if (isset($this->trackData['gsu_id_survey'])) {
                    $this->surveyId = $this->trackData['gsu_id_survey'];
                } elseif (isset($this->trackData['gro_id_survey'])) {
                    $this->surveyId = $this->trackData['gro_id_survey'];
                } elseif (! $this->trackId) {
                    if (isset($this->trackData['gtr_id_track'])) {
                        $this->trackId = $this->trackData['gtr_id_track'];
                    } elseif (isset($this->trackData['gro_id_track'])) {
                        $this->trackId = $this->trackData['gro_id_track'];
                    }
                }
            }
            
            if ((! $this->trackId) && $this->trackEngine) {
                $this->trackId = $this->trackEngine->getTrackId();
            }

            if ($this->trackId && (! $this->surveyId)) {
                // Use the track ID to get the id of the first active survey
                $this->surveyId = $this->db->fetchOne('SELECT gro_id_survey FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ? ORDER BY gro_id_order', $this->trackId);

            }
        }
        // MUtil_Echo::track($this->surveyId, $this->trackId);

        // Load the data
        if ($this->surveyId) {
            $survey = $this->loader->getTracker()->getSurvey($this->surveyId);
            $this->data = $survey->getQuestionInformation($this->locale->getLanguage());
            // MUtil_Echo::track($this->data);
        }

        return parent::hasHtmlOutput();
    }

    public function showAnswers($answers)
    {
        if ($answers) {
            if (is_array($answers)) {
                if (count($answers) == 1) {
                    return reset($answers);
                }

                if (count($answers) > $this->showAnswersLimit) {
                    $border_limit = intval($this->showAnswersLimit / 2);
                    $newAnswers = array_slice($answers, 0, $border_limit);
                    $newAnswers[] = $this->showAnswersRemoved;
                    $newAnswers = array_merge($newAnswers, array_slice($answers, -$border_limit));

                    $answers = $newAnswers;
                }
                return MUtil_Html::raw($this->showAnswersSepStart . implode($this->showAnswersSeparator, $answers) . $this->showAnswersSepEnd);
            } else {
                return MUtil_Html::raw($this->showAnswerTypeStart . $answers . $this->showAnswerTypeEnd);
            }
        } else {
            return MUtil_Html::raw($this->showAnswersNoneStart . $this->showAnswersNone . $this->showAnswersNoneEnd);
        }
    }
}
