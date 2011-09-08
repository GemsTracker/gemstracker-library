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
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @package Gems
 * @subpackage Default
 */

/**
 * Performs bulk-mail action, can be called from a cronjob
 * 
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_EmailAction extends Gems_Default_TokenPlanAction
{
    private $_organizationId = null;
    private $_intervalDays = 7;
    
    /**
     * Constructs
     * @param strings $mode Either 'notmailed' or 'reminder'
     */
    protected function getFilter($mode = null)
    {
        $filter = array(
        	'can_email'           => 1,
            'gto_id_organization' => $this->_organizationId,
            'gtr_active'          => 1,
            'gsu_active'          => 1,
            'grc_success'         => 1,
        	'gto_completion_time' => NULL,
        	'`gto_valid_from` >= DATE_ADD(CURRENT_DATE, INTERVAL -4 WEEK)',
        	'`gto_valid_from` <= DATE_ADD(CURRENT_DATE, INTERVAL 2 WEEK)',
            '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)'
        );
        
        if (isset($mode) && $mode == 'reminder') {
            $filter[] = 'gto_mail_sent_date <= DATE_SUB(CURRENT_DATE, INTERVAL ' . $this->_intervalDays . ' DAY)';
        } else {
            $filter['gto_mail_sent_date'] = NULL;
        }
        
        return $filter;
    }
    
    /**
     * Loads an e-mail template
     * @param integer|null $templateId
     */
    protected function getTemplate($templateId)
    {
        $model = new MUtil_Model_TableModel('gems__mail_templates');

        return $model->loadFirst(array('gmt_id_message' => $templateId));
    }
    
    public function indexAction()
    {
        $this->initHtml();
        
        $model = $this->loader->getTracker()->getTokenModel();
        $model->setCreate(false);
        
        $mailer = new Gems_Email_TemplateMailer($this->escort);
        
        if (isset($this->project->email['automatic'])) {
            $batches = $this->project->email['automatic'];
            $numBatches = count($batches['mode']);
            
            for ($i = 0; $i < $numBatches; $i++) {
                $this->_organizationId = $batches['organization'][$i];
                
                if (isset($batches['days'][$i])) {
                    $this->_intervalDays = $batches['days'][$i];
                }
                
                $this->escort->loadLoginInfo($batches['user'][$i]);
                
                $model->setFilter($this->getFilter($batches['mode'][$i]));
                
                $tokensData = $model->load();
                
                if (count($tokensData)) {
                    $tokens = array();
                    
                    foreach ($tokensData as $tokenData) {
                        $tokens[] = $tokenData['gto_id_token'];
                    }
                    
                    $templateData = $this->getTemplate($batches['template'][$i]);
                    $mailer->setSubject($templateData['gmt_subject']);
                    $mailer->setBody($templateData['gmt_body']);
                    $mailer->setMethod($batches['method'][$i]);
                    $mailer->setFrom($batches['from'][$i]);
                    $mailer->setTokens($tokens);
                    
                    $mailer->process($tokensData);
                }
                
                Gems_Auth::getInstance()->clearIdentity();
                $this->escort->session->unsetAll();
            }
        }
        
        $this->html->append($mailer->getMessages());
        
    }
}