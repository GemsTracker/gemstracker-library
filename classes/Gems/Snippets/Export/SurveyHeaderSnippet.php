<?php

/**
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Header for html/pdf export of a survey
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Gems_Snippets_Export_SurveyHeaderSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * @var \Gems_Tracker_Token
     */
    public $token;

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();
        $html->div($this->token->getSurveyName(), array('class'=>'surveyTitle'), ' ');
        $html->div($this->token->getRoundDescription(), array('class'=>'roundDescription', 'renderClosingTag'=>true));

        return $html;
    }
}