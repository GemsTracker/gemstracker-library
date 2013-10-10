<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_CommTemplateAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    
    protected $createEditSnippets = 'Gems_Snippets_Mail_MailModelFormSnippet';

    public function createModel($detailed, $action)
    {
        $currentLanguage = $this->locale->getLanguage();
        $model = $this->loader->getModels()->getCommtemplateModel($currentLanguage);

        
        if ($detailed) {
            $commTargets = $this->loader->getMailTargets();
            // Translations maybe?
            foreach($commTargets as $name=>$label) {
                
            }
            $model->set('gct_target', 'label', $this->_('Mail Target'), 'multiOptions', $commTargets);
        }

        $model->set('gct_name', 'label', $this->_('Name'), 'size', 50);
        $model->set('gctt_subject', 'label', $this->_('Subject'), 'size', 50);
        if ($detailed) {
            $model->set('gctt_body', 'label', $this->_('Message'), 'elementClass', 'Textarea', 'rows', 4);

            $model->setIfExists('gct_code', 'label', $this->_('Code name'), 'size', 10, 'description', $this->_('Only for programmers.'));
        }
        $model->set('gctt_lang', 'elementClass', 'hidden',  'value', $currentLanguage );

        $allLanguages = $this->util->getLocalized()->getLanguages();
        //MUtil_Echo::track($allLanguages);



        return $model;
    }

    public function getCreateTitle()
    {
        return $this->_('New') . ' ' . $this->_('template');
    }

    public function getEditTitle()
    {
        $data = $this->getModel()->loadFirst();
        $topic = $this->_('template');
        $edit = $this->_('Edit');
        return $edit .' '. $topic . ': ' . $data['gct_name'];
    }

    public function getIndexTitle()
    {
        return $this->_('Email') . ' ' . $this->_('templates');
    }

}
