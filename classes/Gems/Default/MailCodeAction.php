<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class Gems_Default_MailCodeAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = ['mailcodes'];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = [
        'extraSort' => ['gmc_id' => SORT_ASC],
        ];

    /**
     * @var \Gems_Util
     */
    public $util;
    
    /**
     * @inheritDoc
     */
    protected function createModel($detailed, $action)
    {
        $yesNo  = $this->util->getTranslated()->getYesNo();
        
        $model = new \MUtil_Model_TableModel('gems__mail_codes');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('gmc_id', 'label', $this->_('Value'),
                    'description', $this->_('The higher the number, the likelier to mail'),
                    'size', '3',
                    'validators[digits]', 'Digits',
                    'validators[unique]', $model->createUniqueValidator('gmc_id'));

        $model->set('gmc_mail_to_target', 'label', $this->_('Respondent description'),
                    'description', $this->_('Value at the respondent / track level.'),
                    'required', true,
                    'size', '20');
        $model->set('gmc_mail_cause_target', 'label', $this->_('Survey description'),
                    'description', $this->_('Value at the survey level.'),
                    'required', true,
                    'size', '20');
        
        $model->set('gmc_code',        'label', $this->_('Mailcode'),
                    'description', $this->_('Optional code name to link the survey to program code.').
                    'size', 10);

        $model->set('gmc_for_surveys',       'label', $this->_('Surveys'),
                    'elementClass', 'CheckBox',
                    'description', $this->_('This mail code can be assigned to a survey.'),
                    'multiOptions', $yesNo
                    );
        $model->set('gmc_for_respondents',   'label', $this->_('Respondents'),
                    'elementClass', 'CheckBox',
                    'description', $this->_('This mail code can be assigned to a respondent.'),
                    'multiOptions', $yesNo,
                    );
        $model->set('gmc_for_tracks',        'label', $this->_('Tracks'),
                    'elementClass', 'CheckBox',
                    'description', $this->_('This mail code can be assigned to a track.'),
                    'multiOptions', $yesNo,
                    );
        
        \Gems_Model::setChangeFieldsByPrefix($model, 'gmc');

        return $model;
    }
    
    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     * /
    public function getIndexTitle()
    {
        return $this->_('Mail codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('mail code', 'mail codes', $count);
    }
}