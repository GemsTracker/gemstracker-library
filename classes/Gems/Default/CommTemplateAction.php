<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Default_CommTemplateAction extends \Gems_Controller_ModelSnippetActionAbstract
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
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $currentLanguage = $this->locale->getLanguage();
        $model = $this->loader->getModels()->getCommtemplateModel($currentLanguage);

        $commTargets = $this->loader->getMailTargets();

        $model->set('gct_name', 'label', $this->_('Name'), 'size', 50);
        $model->set('gct_target', 'label', $this->_('Mail Target'), 'multiOptions', $commTargets, 'Gems_Default_CommTemplateAction', 'translateTargets');
        
        $translationModel = new \MUtil_Model_TableModel('gems__comm_template_translations', 'gctt');
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

        $model->set('gct_code', 'label', $this->_('Template code'),
                'size', 50,
                'description', $this->_('Optional code name to link the template to program code.')
                );
        $transformer = new \MUtil_Model_Transform_RequiredRowsTransformer();
        $transformer->setRequiredRows($requiredRows);
        $translationModel->addTransformer($transformer);


        $model->addModel($translationModel, array('gct_id_template' => 'gctt_id_template'), 'gctt');

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
        $text = '';
        if (!empty($bbcode)) {
            $text = \MUtil_Markup::render($bbcode, 'Bbcode', 'Html');
        }


        $div = \MUtil_Html::create()->div(array('class' => 'mailpreview'));
        $div->raw($text);

        return $div;
    }

    public static function displayMultipleSubjects($subValuesArray)
    {
        $html = \MUtil_Html::create()->div();
        $output = '';

        $multi = false;
        if (count($subValuesArray) > 1) {
            $multi = true;
        }
        foreach($subValuesArray as $subitem) {
            if (!empty($subitem['gctt_subject'])) {
                $paragraph = $html->p();
                if ($multi) {
                    $paragraph->strong()->append($subitem['gctt_lang'].':');
                    $paragraph->br();
                }
                $paragraph[] = $subitem['gctt_subject'];
            }
        }
        return $html;
    }

    public static function translateTargets($targetName)
    {
        return $this->_($targetName);
    }

}
