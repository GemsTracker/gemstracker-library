<?php
/**
 * Copyright (c) 2012, Erasmus MC
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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard controller to export respondent data to html
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */
class Gems_Default_RespondentExportAction extends Gems_Controller_Action
{
    public $useHtmlView = true;
    
    protected $_wkhtmltopdfLocation = "";
    
    protected $_groupedSurveySnippet = 'TrackAnswersModelSnippet';
    protected $_singleSurveySnippet  = 'AnswerModelSnippet';
    
    public function init()
    {
        parent::init();
        
        if (isset($this->project->export) && isset($this->project->export['wkhtmltopdf'])) {
            $this->_wkhtmltopdfLocation = $this->project->export['wkhtmltopdf'];
        }
    }

    /**
     * Constructs the form 
     * 
     * @return Gems_Form_TableForm
     */
    protected function _getForm()
    {
        $form = new Gems_Form_TableForm();
        $form->setAttrib('target', '_blank');
        
        $element = new Zend_Form_Element_Text('id');
        $element->setLabel($this->_('Respondent number'));
        $element->setDescription($this->_('Separate multiple respondents with a comma (,)'));
        $form->addElement($element);
        
        $element = new Zend_Form_Element_Checkbox('group');
        $element->setLabel($this->_('Group surveys'));
        $form->addElement($element);
        
        $outputFormats = array('html' => 'HTML');
        if (!empty($this->_wkhtmltopdfLocation)) {
            $outputFormats['pdf'] = 'PDF';
        }
        
        $element = new Zend_Form_Element_Select('format');
        $element->setLabel($this->_('Output format'));
        $element->setMultiOptions($outputFormats);
        $form->addElement($element);
        
        $element = new Zend_Form_Element_Submit('export');
        $element->setLabel($this->_('Export'))
                ->setAttrib('class', 'button');
        $form->addElement($element);
        
        return $form;
    }
    
    /**
     * Calls 'wkhtmltopdf' to convert HTML to PDF
     * 
     * @param  string $content The HTML source
     * @return string The converted PDF file
     * @throws Exception
     */
    protected function _convertToPdf($content)
    {
        $tempInputFilename  = tempnam(GEMS_ROOT_DIR . '/var/tmp/', 'gemshtml') . '.html';
        $tempOutputFilename = tempnam(GEMS_ROOT_DIR . '/var/tmp/', 'gemspdf')  . '.pdf';

        if (empty($tempInputFilename) || empty($tempOutputFilename)) {
            throw new Exception("Unable to create temporary file(s)");
        }

        file_put_contents($tempInputFilename, $content);

        $lastLine = exec(escapeshellarg($this->_wkhtmltopdfLocation) . ' ' . escapeshellarg($tempInputFilename)
            . ' ' . escapeshellarg($tempOutputFilename), $outputLines, $return);

        if ($return > 0) {
            @unlink($tempInputFilename);
            @unlink($tempOutputFilename);
            
            throw new Exception(sprintf($this->_('Unable to run PDF conversion: "%s"'), $lastLine));
        }
        
        $pdfContents = file_get_contents($tempOutputFilename);
         
        @unlink($tempInputFilename);
        @unlink($tempOutputFilename);

        return $pdfContents;
    }

    /**
     * Exports all the tokens of a single track, grouped by round
     * 
     * @param Gems_Tracker_RespondentTrack $track
     */
    protected function _exportTrackTokens(Gems_Tracker_RespondentTrack $track)
    {
        $groupSurveys = $this->getRequest()->getParam('group');
        $token = $track->getFirstToken();
        $engine = $track->getTrackEngine();
        $surveys = array();
        
        $table = $this->html->table(array('class' => 'browser'));
        $table->th($this->_('Survey'))
              ->th($this->_('Round'))
              ->th($this->_('Token'))
              ->th($this->_('Status'));
        $this->html->br();

        while ($token) {
            $missed = false;
            $validUntil = $token->getValidUntil();
            
            if (!empty($validUntil)) {
                $missed = $validUntil->isEarlier(new Zend_Date());
            }
            
            $table->tr()->td($token->getSurveyName())
                        ->td(($engine->getTrackType() == 'T' ? $token->getRoundDescription() : $this->_('Single Survey')))
                        ->td(strtoupper($token->getTokenId()))
                        ->td(($token->isCompleted() ? $this->_('Completed') : ($missed ? $this->_('Missed') : $this->_('Open'))));
            
            if ($engine->getTrackType() == 'S' || !$groupSurveys) { 
                if ($token->isCompleted()) {
                    $this->html->span()->b($token->getSurveyName() . ($token->getRoundDescription() ? ' (' . $token->getRoundDescription() . ')' : ''));
                    $this->addSnippet($this->_singleSurveySnippet, 'token', $token, 'tokenId', $token->getTokenId(),
                    	'showHeaders', false, 'showButtons', false, 'showSelected', false, 'showTakeButton', false);
                    
                    $this->html->br();
                }
            } else {
                if (!isset($surveys[$token->getSurveyId()])) {
                    $surveys[$token->getSurveyId()] = true;
                            
                    $this->html->span()->b($token->getSurveyName());
                    $this->addSnippet($this->_groupedSurveySnippet, 'token', $token, 'tokenId', $token->getTokenId(),
                    	'showHeaders', false, 'showButtons', false, 'showSelected', false, 'showTakeButton', false);
                    
                    $this->html->br();
                }
            }
            
            $token = $token->getNextToken();
        }
    }
    
    /**
     * Exports a single track
     * 
     * @param Gems_Tracker_RespondentTrack $track
     */
    protected function _exportTrack(Gems_Tracker_RespondentTrack $track)
    {
        if ($track->getReceptionCode() != GemsEscort::RECEPTION_OK) {
            return;
        }
        
        $trackModel = $this->loader->getTracker()->getRespondentTrackModel();
        $trackModel->resetOrder();
        $trackModel->set('gtr_track_name',    'label', $this->_('Track'));
        $trackModel->set('gr2t_track_info',   'label', $this->_('Description'),
            'description', $this->_('Enter the particulars concerning the assignment to this respondent.'));
        $trackModel->set('assigned_by',       'label', $this->_('Assigned by'));
        $trackModel->set('gr2t_start_date',   'label', $this->_('Start'),
            'formatFunction', $this->util->getTranslated()->formatDate,
            'default', MUtil_Date::format(new Zend_Date(), 'dd-MM-yyyy'));
        $trackModel->set('gr2t_reception_code');
        $trackModel->set('gr2t_comment',       'label', $this->_('Comment'));
        $trackModel->setFilter(array('gr2t_id_respondent_track' => $track->getRespondentTrackId()));
        $trackData = $trackModel->loadFirst();
        
        $this->html->h3($this->_('Track') . ' ' . $trackData['gtr_track_name']);
        
        $bridge = new MUtil_Model_VerticalTableBridge($trackModel, array('class' => 'browser'));
        $bridge->setRepeater(MUtil_Lazy::repeat(array($trackData)));
        $bridge->th($this->_('Track information'), array('colspan' => 2));
        $bridge->setColumnCount(1);
        foreach($trackModel->getItemsOrdered() as $name) {
            if ($label = $trackModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }
        
        $table = $bridge->getTable();
        
        foreach ($track->getFieldData() as $field => $value) {
            if (is_int($field)) {
                continue;
            }
            
            $table->tr()->th($field)->td($value);
        }
        
        $this->html[] = $table;
        $this->html->br();

        $this->_exportTrackTokens($track);
        
        $this->html->hr();
    }
    
    /**
     * Exports a single respondent
     * 
     * @param string $respondentId
     */
    protected function _exportRespondent($respondentId)
    {
        $respondentModel = $this->loader->getModels()->getRespondentModel(false);
        $respondentModel->setFilter(array('gr2o_patient_nr' => $respondentId));
        $respondentData = $respondentModel->loadFirst();
        
        if (empty($respondentData)) {
            $this->html->p()->b(sprintf('Unknown respondent %s', $respondentId));
            return;
        }
        
        $bridge = new MUtil_Model_VerticalTableBridge($respondentModel, array('class' => 'browser'));
        $bridge->setRepeater(MUtil_Lazy::repeat(array($respondentData)));
        $bridge->th($this->_('Respondent information'), array('colspan' => 4));
        $bridge->setColumnCount(2);
        foreach($respondentModel->getItemsOrdered() as $name) {
            if ($label = $respondentModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }
        
        $this->html->h2($respondentId);
        $this->html[] = $bridge->getTable();
        $this->html->hr();
        
        $tracker = $this->loader->getTracker();
        $tracks = $tracker->getRespondentTracks($respondentData['gr2o_id_user'], $respondentData['gr2o_id_organization']);
        
        foreach ($tracks as $trackId => $track) {
            $this->_exportTrack($track);
        }
    }
    
    /**
     * Renders the entire report (including layout)
     * 
     * @param string[] $respondentId
     */
    protected function _render($respondents)
    {
        $this->html = new MUtil_Html_Sequence();
        $this->html->h1($this->_('Respondent report'));
        
        $table = $this->html->table(array('class' => 'browser'));
        
        $table->th($this->_('Report information'), array('colspan' => 2));
        $table->tr()->th($this->_('Generated by'))
                    ->td($this->loader->getCurrentUser()->getFullName());
        $table->tr()->th($this->_('Generated on'))
                    ->td(new Zend_Date());
        $table->tr()->th($this->_('Organization'))
                    ->td($this->loader->getCurrentUser()->getCurrentOrganization()->getName());
        
        $this->html->br();
        
        foreach ($respondents as $respondentId) {
            $this->_exportRespondent($respondentId);
            
            $this->html->div('', array('style' => 'height: 100px'));
        }
        
        $this->escort->menu->setVisible(false);
        if ($this->escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $this->escort->layoutSwitch();
        }
        $this->escort->postDispatch($this->getRequest());
        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $this->view->layout()->content = $this->html->render($this->view);

        $content = $this->view->layout->render();
        
        if ($this->getRequest()->getParam('format') == 'pdf') {
            $content = $this->_convertToPdf($content);
            $filename = 'respondent-export-' . strtolower($respondentId) . '.pdf';
            
			header('Content-Type: application/x-download');
            header('Content-Length: '.strlen($content));
            header('Content-Disposition: inline; filename="'.$filename.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }
        
        echo $content;
        
        $this->escort->menu->setVisible(true);
    }
     
    public function indexAction()
    {
        $form = $this->_getForm();
        $this->html->h2($this->_('Export respondent'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;
        
        $request = $this->getRequest();
        
        $form->populate($request->getParams());
        
        if ($request->isPost()) {
            $respondents = explode(',', $request->getParam('id'));

            $this->_render($respondents);
        }
    }
}