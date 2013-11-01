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
 * @version    $id CommTemplateAction.php
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

    protected $createEditSnippets = 'Mail_MailModelFormSnippet';

    public $showSnippets = 'Mail_CommTemplateShowSnippet';

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
    public function createModel($detailed, $action)
    {
        $currentLanguage = $this->locale->getLanguage();
        $model = $this->loader->getModels()->getCommtemplateModel($currentLanguage);

        
        if ($detailed) {
            $commTargets = $this->loader->getMailTargets();
            $translatedCommTargets = array();
            foreach($commTargets as $name=>$label) {
                $translatedCommTargets[$name] = $this->_($label);
            }
            $model->set('gct_target', 'label', $this->_('Mail Target'), 'multiOptions', $translatedCommTargets);
        }

        $model->set('gct_name', 'label', $this->_('Name'), 'size', 50);

        $translationModel = new MUtil_Model_TableModel('gems__comm_template_translations', 'gctt');
        if ($action === 'index') {
            $translationModel->set('gctt', 'label', $this->_('Subject'), 'size', 50, 'formatFunction', array('Gems_Default_CommTemplateAction', 'displayMultipleSubjects'));
        } else {
            $translationModel->set('gctt_subject', 'label', $this->_('Subject'), 'size', 50);
        }
        if ($detailed) {
            $translationModel->set('gctt_body', 
                'label', $this->_('Message'), 
                'elementClass', 'textarea',
                'decorators', array('CKEditor'),
                'rows', 4,
                'formatFunction', array('Gems_Default_CommTemplateAction', 'bbToHtml')
            );
        }

        if ($this->project->getEmailMultiLanguage()) {
            $allLanguages = $this->util->getLocalized()->getLanguages();
            ksort($allLanguages);
            $requiredRows = array();
            foreach($allLanguages as $code=>$language) {
                $requiredRows[]['gctt_lang'] = $code;
            }
        } else {
            $defaultLanguage = $this->project->getLocaleDefault();
            $requiredRows[]['gctt_lang'] = $defaultLanguage;

            $translationModel->setFilter(array('gctt_lang' => $defaultLanguage));
        }

        $transformer = new MUtil_Model_Transform_RequiredRowsTransformer();
        $transformer->setRequiredRows($requiredRows);
        $translationModel->addTransformer($transformer);


        $model->addModel($translationModel, array('gct_id_template' => 'gctt_id_template'), 'gctt');
        
        MUtil_Echo::track($action);
        return $model;
    }

    public function getCreateTitle()
    {
        return $this->_('New template');
    }

    public function getEditTitle()
    {
        $data = $this->getModel()->loadFirst();

        return sprintf($this->_('Edit template: %s'), $data['gct_name']);
    }

    public function getIndexTitle()
    {
        return $this->_('Email templates');
    }

    public static function bbToHtml($bbcode) {
        $text = MUtil_Markup::render($bbcode, 'Bbcode', 'Html'); 

        $div = MUtil_Html::create()->div(array('class' => 'mailpreview'));
        $div->raw($text);
        
        return $div;
    }

    public static function displayMultipleSubjects($subValuesArray)
    {
        $html = MUtil_Html::create()->div();
        $output = '';
        foreach($subValuesArray as $subitem) {
            if (!empty($subitem['gctt_subject'])) {
                $paragraph = $html->p();
                $paragraph->strong()[] = $subitem['gctt_lang'].':';
                $paragraph->br();
                $paragraph[] = $subitem['gctt_subject'];
            }
        }
        MUtil_Echo::track($subValuesArray);
        return $html;
    }

}
