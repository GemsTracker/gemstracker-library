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

    /**
     * Constructs the form 
     * 
     * @return Gems_Form_TableForm
     */
    protected function _getForm()
    {
        $form = new Gems_Form_TableForm();
        $form->setAttrib('target', '_blank');
        
        $element = new Zend_Form_Element_Text('respondentId');
        $element->setLabel($this->_('Respondent number'));
        $form->addElement($element);
        
        $element = new Zend_Form_Element_Submit('export');
        $element->setLabel($this->_('Export'))
                ->setAttrib('class', 'button');
        $form->addElement($element);
        
        return $form;
    }

    /**
     * Exports all the tokens of a single track, grouped by round
     * 
     * @param Gems_Tracker_RespondentTrack $track
     */
    protected function _exportTrackTokens(Gems_Tracker_RespondentTrack $track)
    {
        $engine = $track->getTrackEngine();
        
        $table = $this->html->table(array('class' => 'browser'));
        $table->th($this->_('Survey'))
              ->th($this->_('Round'))
              ->th($this->_('Token'))
              ->th($this->_('Completed'));
        
        $this->html->br();

        $token = $track->getFirstToken();
        
        while ($token) {
            $table->tr()->td($token->getSurveyName())
                        ->td(($engine->getTrackType() == 'T' ? $token->getRoundDescription() : $this->_('Single Survey')))
                        ->td(strtoupper($token->getTokenId()))
                        ->td(($token->isCompleted() ? $this->_('Yes') : $this->_('No')));
            
            if ($token->isCompleted()) {
                $this->html->span()->b($token->getSurveyName() . ($token->getRoundDescription() ? ' (' . $token->getRoundDescription() . ')' : ''));
                $this->addSnippet('AnswerModelSnippet', 'token', $token, 'tokenId', $token->getTokenId(),
                	'showHeaders', false, 'showButtons', false, 'showSelected', false, 'showTakeButton', false);
                
                $this->html->br();
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
            'default', MUtil_Date::format(new Zend_date(), 'dd-MM-yyyy'));
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
        
        $bridge = new MUtil_Model_VerticalTableBridge($respondentModel, array('class' => 'browser'));
        $bridge->setRepeater(MUtil_Lazy::repeat(array($respondentData)));
        $bridge->th($this->_('Respondent information'), array('colspan' => 4));
        $bridge->setColumnCount(2);
        foreach($respondentModel->getItemsOrdered() as $name) {
            if ($label = $respondentModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }
        
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
     * @param string $respondentId
     */
    protected function _render($respondentId)
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
        
        $this->_exportRespondent($respondentId);
        
        $this->escort->menu->setVisible(false);
        $this->escort->layoutSwitch();
        $this->escort->postDispatch($this->getRequest());
        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $htmlData = $this->html->render($this->view);
        $this->view->layout()->content = $htmlData;

        echo $this->view->layout->render();
        $this->escort->menu->setVisible(true);
    }
     
    public function indexAction()
    {
        $form = $this->_getForm();
        $this->html->h2($this->_('Export respondent'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $form->populate($request->getPost());
            
            $respondentId = $request->getParam('respondentId');

            if (!empty($respondentId)) {
                $this->_render($respondentId);
            }
        }
    }
}