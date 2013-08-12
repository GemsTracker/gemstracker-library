<?php
/**
 * BarChartSnippet
 * 
 * the BarChart snippet provides a single dimension barchart based on one question_code for all surveys using a specific survey_code
 * 
 * Options are:
 *      $min / $max             for min and max values for the chart
 *      $showButtons            show buttons underneath the chart for cancel / print
 *      $showHeaders            show a header for the overview (chart always has a header)
 *      $question_code
 *      $survey_code
 * 
 *
 * @author Menno Dekker
 */
class Gems_Snippets_Survey_Display_BarChartSnippet extends MUtil_Snippets_SnippetAbstract {
    
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
     * The survey code to select on
     * 
     * @var string
     */
    public $survey_code = null;
    
    /**
     *
     * @var Gems_Loader
     */
    public $loader;
    
    /**
     * @var Zend_Controller_Request_Abstract
     */
    public $request;
    
    /**
     *
     * @var Gems_Tracker_Token
     */       
    public $token;
       
    public function getChart()
    {
        $token = $this->token;
        $data = $this->data;
        
        $questions = $token->getSurvey()->getQuestionList($this->loader->getCurrentUser()->getLocale());
        
        $question = isset($questions[$this->question_code]) ? $questions[$this->question_code] : $this->question_code;
        
        $html = MUtil_Html::create();
        $wrapper = $html->div(null, array('class'=>'barchart-wrapper'));
        $wrapper->div('', array('class'=>'header'))
                ->append($token->getSurveyName())
                ->append($html->br())
                ->append($html->i($question));
        $chart = $wrapper->div(null, array('class'=>'barchart'));
        
        // the legend, only for printing since it is a hover for screen
        $legend = $html->div('', array('class'=>'legend'));
        $legendrow = $html->div('', array('class'=>'legendrow header'));
        $legendrow[] = $html->div($this->_('Date'), array('class'=> 'date'));
        $legendrow[] = $html->div($this->_('Round'), array('class'=> 'round', 'renderClosingTag'=>true));
        $legendrow[] = $html->div($this->_('Value'), array('class'=> 'value', 'renderClosingTag'=>true));
        $legend[] = $legendrow;
       
        $range = $this->max - $this->min;

        $col = 0;
        $maxcols = 5;
        $chart[] = $html->div($this->max, array('class'=>'max'));
        $chart[] = $html->div($this->min, array('class'=>'min'));
        foreach ($data as $row) {
            $token = $this->loader->getTracker()->getToken($row);
            
            $answers = $token->getRawAnswers();
            if (array_key_exists($this->question_code, $answers)) {
                $value = (float) $answers[$this->question_code];        // Cast to number
                $height  = ($value - $this->min) / $range * 100;
                $height = max(min($height, 100),10);    // Add some limits
                $chart[] = $html->div(Mutil_Html::raw('&nbsp;'), array('class' => 'spacer bar'));
                $valueBar = $html->div('', array('class' => 'bar col'.(($col % $maxcols) + 1), 'style' => sprintf('height: %s%%;', $height)));
                $date = $token->getCompletionTime()->get('dd-MM-yyyy HH:mm');
                $info = $html->div($date, array('class'=>'info'));
                $info[] = $html->br();
                $info[] = $answers[$this->question_code];   // The raw answer
                $info[] = $html->br();
                $info[] = $token->getRoundDescription();
                $legendrow = $html->div('', array('class'=>'legendrow'));
                $legendrow[] = $html->div($date, array('class'=> 'date', 'renderClosingTag'=>true));
                $legendrow[] = $html->div($token->getRoundDescription(), array('class'=> 'round', 'renderClosingTag'=>true));
                $legendrow[] = $html->div($answers[$this->question_code], array('class'=> 'value', 'renderClosingTag'=>true));
                $legend[] = $legendrow;
                if (empty($value))  {
                    $value = 'N/A';
                }
                // Link the value to the answer view in a new window (not the bar to avoid usability issues on touch devices)
                $valueBar[] = $html->a(array('controller'=>'track', 'action'=>'answer', MUtil_Model::REQUEST_ID => $token->getTokenId()), array('target'=>'_blank'), $html->div($value, array('class'=>'value')));
                $valueBar[] = $info;
                
                $chart[] = $valueBar;
                
                $col ++;
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
        $view->headLink()->prependStylesheet($view->serverUrl() . GemsEscort::getInstance()->basepath->getBasePath() . '/gems/css/barchart.less', 'screen,print');            

        $htmlDiv   = MUtil_Html::create()->div(' ', array('class'=>'barchartcontainer'));
        
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
            $buttonDiv->actionLink(array(), $this->_('Back'), array('onclick' => 'history.go(-1);'));
            $buttonDiv->actionLink(array(), $this->_('Print'), array('onclick' => 'window.print();'));
        }
        return $htmlDiv;
    }
    
    public function afterRegistry() {
        parent::afterRegistry();
        
        $orgId     = $this->request->getParam(MUtil_Model::REQUEST_ID2);
        $patientNr = $this->request->getParam(MUtil_Model::REQUEST_ID1);
        
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
        
        if (!empty($data)) {
            $firstRow = reset($data);
            $this->token = $this->loader->getTracker()->getToken($firstRow);
        }
    }
}