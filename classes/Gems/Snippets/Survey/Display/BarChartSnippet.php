<?php
/**
 * Copyright (c) 2014, Erasmus MC
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
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * The BarChart snippet provides a single dimension barchart based on one question_code for all surveys using a specific survey_code
 *
 * Options are:
 *      $min / $max             for min and max values for the chart
 *      $showButtons            show buttons underneath the chart for cancel / print
 *      $showHeaders            show a header for the overview (chart always has a header)
 *      $question_code          single question code or array of questioncodes to get a grouped barchart
 *      $survey_code
 *
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Snippets_Survey_Display_BarChartSnippet extends \MUtil_Snippets_SnippetAbstract {

    /**
     * Switch to put the display of the cancel and print buttons.
     *
     * @var boolean
     */
    protected $showButtons = true;

    /**
     * Switch to put the display of the headers on or off
     *
     * @var boolean
     */
    protected $showHeaders = true;

    /**
     * The maximum value
     *
     * @var int
     */
    protected $max = 100;

    /**
     * Minimum value
     *
     * @var int
     */
    protected $min = 0;

    /**
     * The question code to use for the chart
     *
     * @var string
     */
    public $question_code = null;

    /**
     * The question text to use for the chart
     *
     * @var string
     */
    public $question_text = null;

    /**
     * The survey code to select on
     *
     * @var string
     */
    public $survey_code = null;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    public $request;

    public $data;

    /**
     * Array of rulers, defaults to each 10% a ruler
     *
     * percentage (0-100)
     * value      (between min/max)
     * class       positive/negative for green/red color
     *
     * @var array
     */
    public $rulers = array();

    /**
     * Show gridlines every 10%
     *
     * @var boolean
     */
    public $grid = true;

    /**
     *
     * @var \Gems_Tracker_Token
     */
    public $token;

    protected function doRulers($chart)
    {
        $html = \MUtil_Html::create();

        if ($this->grid) {
            $rulers = array_merge(
            array(
                array('percentage' => 10),
                array('percentage' => 20),
                array('percentage' => 30),
                array('percentage' => 40),
                array('percentage' => 50),
                array('percentage' => 60),
                array('percentage' => 70),
                array('percentage' => 80),
                array('percentage' => 90)
            ),
            $this->rulers
            );
        } else {
            $rulers = $this->rulers;
        }

        foreach ($rulers as $ruler)
        {
            $defaults = array('value'=>0, 'class'=>'');
            $ruler = $ruler + $defaults;
            if (!isset($ruler['percentage'])) {
                $position = 100 - $this->getPercentage($ruler['value']);
            } else {
                $position = 100 - $ruler['percentage'];
            }

            // Only draw rulers that are in the visible range
            if ($position >= 0 && $position <= 100) {
                if (array_key_exists('label', $ruler) && !empty($ruler['label'])) {
                    $chart[] = $html->div($ruler['label'], array('style'=>sprintf('top: %s%%;', $position), 'class'=>'label', 'renderClosingTag'=>true, 'title'=>$ruler['label']));
                }
                $chart[] = $html->div('', array('style'=>sprintf('top: %s%%;', $position), 'class'=>'ruler ' . $ruler['class'], 'renderClosingTag'=>true));
            }
        }
    }

    public function getChart()
    {
        $token = $this->token;
        $data = $this->data;

        $questions = $token->getSurvey()->getQuestionList($this->loader->getCurrentUser()->getLocale());
        $surveyName = $token->getSurveyName();

        // Convert questioncode to array if not already so
        $questionCodes = $this->question_code;
        if (! is_array($questionCodes)) {
            $questionCodes = (array) $questionCodes;
        }

        if (count($questionCodes)>1) {
            $grouped = true;
        } else {
            $grouped = false;
        }

        if (empty($this->question_text)) {
            if ($grouped) {
                $question = join(', ', $questionCodes);
            } else {
                $question = isset($questions[$questionCodes[0]]) ? $questions[$questionCodes[0]] : $questionCodes[0];
            }
        } else {
            $question = $this->question_text;
        }

        $html = \MUtil_Html::create();
        $wrapper = $html->div(null, array('class'=>'barchart-wrapper'));
        $wrapper->div('', array('class'=>'header'))
                ->append($surveyName)
                ->append($html->br())
                ->append($html->i($question));
        $chart = $wrapper->div(null, array('class'=>'barchart'));

        // the legend, only for printing since it is a hover for screen
        $legend = $html->div('', array('class'=>'legend'));
        $legendrow = $html->div('', array('class'=>'legendrow header'));
        $legendrow[] = $html->div($this->_('Date'), array('class'=> 'date'));
        $legendrow[] = $html->div($this->_('Round'), array('class'=> 'round', 'renderClosingTag'=>true));
        if ($grouped) {
            $legendrow[] = $html->div($this->_('Code'), array('class'=> 'code', 'renderClosingTag'=>true));
        }
        $legendrow[] = $html->div($this->_('Value'), array('class'=> 'value', 'renderClosingTag'=>true));
        $legend[] = $legendrow;

        $range = $this->max - $this->min;

        $maxcols = 5;
        $chart[] = $html->div($this->max, array('class'=>'max'));
        $chart[] = $html->div($this->min, array('class'=>'min'));

        $this->doRulers($chart);

        foreach ($data as $row) {
            $token = $this->loader->getTracker()->getToken($row)->refresh();
            if ($token->getReceptionCode()->isSuccess() && $token->isCompleted()) {
                $answers = $token->getRawAnswers();
                foreach ($questionCodes as $idx => $questionCode)
                {
                    if (array_key_exists($questionCode, $answers)) {
                        $value = (float) $answers[$questionCode];        // Cast to number
                        $height = max(min($this->getPercentage($value), 100),10);    // Add some limits

                        $valueBar = $html->div('', array(
                            'class' => 'bar col'.(($idx % $maxcols) + 1),
                            'style' => sprintf('height: %s%%;', $height),
                            // onclick can be usability issue for touch devices
                            'onclick' => 'location.href=\'' . new \MUtil_Html_HrefArrayAttribute(array('controller'=>'track', 'action'=>'answer', \MUtil_Model::REQUEST_ID => $token->getTokenId() )) . '\';'
                            ));
                        $date = $token->getCompletionTime()->get('dd-MM-yyyy HH:mm');
                        $info = $html->div($date, array('class'=>'info'));
                        $info[] = $html->br();
                        if ($grouped) {
                            $question = isset($questions[$questionCode]) ? $questions[$questionCode] : '';
                            $info[] = $questionCode;
                            $info[] = $html->br();
                            $info[] = $question;
                            $info[] = $html->br();
                        }
                        $info[] = $answers[$questionCode];   // The raw answer
                        $info[] = $html->br();
                        $info[] = $token->getRoundDescription();
                        $legendrow = $html->div('', array('class'=>'legendrow'));
                        $legendrow[] = $html->div($date, array('class'=> 'date', 'renderClosingTag'=>true));
                        $legendrow[] = $html->div($token->getRoundDescription(), array('class'=> 'round', 'renderClosingTag'=>true));
                        if ($grouped) {
                            $legendrow[] = $html->div($questionCode, array('class'=> 'code', 'renderClosingTag'=>true));
                        }
                        $legendrow[] = $html->div($answers[$questionCode], array('class'=> 'value', 'renderClosingTag'=>true));
                        $legend[] = $legendrow;
                        if (empty($value))  {
                            $value = 'N/A';
                        }
                        // Link the value to the answer view in a new window (not the bar to avoid usability issues on touch devices)
                        //$valueBar[] = $html->a(array('controller'=>'track', 'action'=>'answer', \MUtil_Model::REQUEST_ID => $token->getTokenId()), array('target'=>'_blank'), $html->div($value, array('class'=>'value')));
                        $valueBar[] = $info;

                        $chart[] = $valueBar;
                    }
                }
                // Add spacer between (groups of) bars
                if ($grouped) {
                    $class = 'spacer wide bar';
                } else {
                    $class = 'spacer bar';
                }
                $chart[] = $html->div(Mutil_Html::raw('&nbsp;'), array('class' => $class));
            }
        }
        $wrapper[] = $legend;

        return $wrapper;
    }

    /**
     * Copied from parent, but insert chart instead of table after commented out part
     *
     * @param \Zend_View_Abstract $view
     * @return type
     */
    public function getHtmlOutput(\Zend_View_Abstract $view) {
        //$view->headLink()->prependStylesheet($view->serverUrl() . \GemsEscort::getInstance()->basepath->getBasePath() . '/gems/css/barchart.less', 'screen,print');

        $htmlDiv   = \MUtil_Html::create()->div(' ', array('class'=>'barchartcontainer'));

        if ($this->showHeaders) {
            if (isset($this->token)) {
                $htmlDiv->h3(sprintf($this->_('Overview for patient number %s'), $this->token->getPatientNumber()));

                $htmlDiv->pInfo(sprintf(
                        $this->_('Overview for patient number %s: %s.'),
                        $this->token->getPatientNumber(),
                        $this->token->getRespondentName()))
                        ->appendAttrib('class', 'noprint');
            } else {
                $htmlDiv->pInfo($this->_('No data present'));
            }
        }

        if (!empty($this->data)) {
            $htmlDiv->append($this->getChart());     // Insert the chart here
        }


        if ($this->showButtons) {
            $buttonDiv = $htmlDiv->buttonDiv();
            $buttonDiv->actionLink(array(), $this->_('Back'), array('onclick' => 'window.history.go(-1); return false;'));
            $buttonDiv->actionLink(array(), $this->_('Print'), array('onclick' => 'window.print();'));
        }

        // Make vertically resizable
        $view = \Zend_Layout::getMvcInstance()->getView();
        /*$jquery = $view->jQuery();
        $jquery->enable();*/
        \MUtil_JQuery::enableView($view);

        // We need width 100% otherwise it will look strange in print output
        $view->jQuery()->addOnLoad("$('.barchart').resizable({
            handles: 's',
            resize: function( event, ui ) { ui.element.css({ width: '100%'}); },
            minHeight: 150
            });");

        return $htmlDiv;
    }

    public function afterRegistry() {
        parent::afterRegistry();

        $orgId     = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
        $patientNr = $this->request->getParam(\MUtil_Model::REQUEST_ID1);

        if (! $this->data) {
            $data = $this->loader->getTracker()->getTokenSelect()
                    ->andRespondentOrganizations()
                    ->andReceptionCodes(array())
                    ->andSurveys()
                    ->forWhere('gsu_code = ?', $this->survey_code)
                    ->forWhere('grc_success = 1')
                    ->forWhere('gr2o_id_organization = ?', $orgId)
                    ->forWhere('gr2o_patient_nr = ?', $patientNr)
                    ->order('gto_completion_time')
                    ->fetchAll();

            $this->data = $data;
        }

        if (!empty($this->data)) {
            $firstRow = reset($this->data);
            if (array_key_exists('gto_id_token', $firstRow)) {
                $this->token = $this->loader->getTracker()->getToken($firstRow)->refresh();
            }
        }
    }

    /**
     * Return the percentage in the range between min and max for this chart
     *
     * @param number $value
     * @return float
     */
    private function getPercentage($value)
    {
        $percentage = ($value - $this->min) / ($this->max - $this->min) * 100;

        return $percentage;
    }
}