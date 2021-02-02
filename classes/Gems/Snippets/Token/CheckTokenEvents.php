<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Tracker\Mock\TokenReadonly;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class CheckTokenEvents extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var \Zend_Locale
     */
    protected $locale;
    
    /**
     * @var \MUtil_Registry_Source
     */
    protected $source;
    
    /**
     * @var \Gems_Tracker_Token
     */
    protected $token;
    
    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->token instanceof \Gems_Tracker_Token;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $checkToken = new TokenReadonly($this->token);
        $this->source->applySource($checkToken);

        $checkSurvey = $checkToken->getSurvey();

        $html = $this->getHtmlSequence();

        $html->h1(sprintf($this->_('Token event overview for token %s'), $checkToken->getTokenId()));

        $html->h2($this->_('Token info'));
        
        $tInfo = [
            $this->_('Token') => $checkToken->getTokenId(),
            $this->_('Track') => $checkToken->getTrackName(),
            $this->_('Round') => $checkToken->getRoundDescription(),
            $this->_('Survey') => $checkToken->getSurveyName(),
            $this->_('Status') => $checkToken->getStatus(),
            ];
        $html->div($this->showArrayTable($tInfo, $this->_('Token settings')), ['class' => 'leftFloat']);
        $html->div($this->showArrayTable(array_filter($checkToken->getRawAnswers()), $this->_('Current raw answers')), ['class' => 'leftFloat']);
        
        $beforeEvent = $checkSurvey->getSurveyBeforeAnsweringEvent();
        $html->h2($this->_('Before answering'), ['style' => 'clear: both;']);
        if ($beforeEvent) {
            $html->pInfo($this->_('This token has this before answering event: '))
                ->strong($beforeEvent->getEventName());
            
            $answers = $checkToken->getRawAnswers();
            $checkToken->getUrl($this->locale, $this->currentUser->getUserId());
            
            $currentAnswers = $checkToken->getMockChanges('setRawAnswers', 'answers');
            $currentLog     = $checkToken->getMockChanges('log');

            if ($currentAnswers) {
                $html->div($this->showArrayTable($currentAnswers, $this->_('Data changed by this event')), ['class' => 'leftFloat']);
            } else {
                $html->pInfo($this->_('This event currently does not change any data for this token.'), ['class' => 'leftFloat']);
            }
            if ($currentLog) {
                $div = $html->div(['class' => 'leftFloat']);
                $div->pInfo($this->_('Actions currently taken by this event:'));
                $div->ol($currentLog);
            }
            $checkToken->unsetRawAnswers();
            $checkToken->getUrl($this->locale, $this->currentUser->getUserId());
            $emptyAnswers = $checkToken->getMockChanges('setRawAnswers', 'answers');
            if ($emptyAnswers && ($emptyAnswers != $currentAnswers)) {
                $html->div($this->showArrayTable($emptyAnswers, $this->_('Data that would be changed if empty')), ['class' => 'leftFloat']);
                $emptyLog = $checkToken->getMockChanges('log');
                if ($emptyLog) {
                    $div = $html->div(['class' => 'leftFloat']);
                    $div->pInfo($this->_('Actions taken by this event while empty:'));
                    $div->ol($emptyLog);
                }
            }
            
        } else {
            $html->pInfo($this->_('This token has no before answering event.'));
        }
        $html->div(['style' => 'clear: both;', 'renderClosingTag' => true,]);

        // \MUtil_Echo::track($checkToken->getMockChanges());
        
        return $html;
    }
    
    protected function showArrayTable(array $data, $caption)
    {
        $table = new \MUtil_Html_TableElement();
        $table->class = 'displayer table table-condensed table-bordered';
        $tr = $table->thead()->tr();
        $tr->td(['colspan' => 2])->strong($caption);
        $tr = $table->thead()->tr();
        $tr->th($this->_('Field'));
        $tr->th($this->_('Value'));
        foreach ($data as $key => $value) {
            $tr = $table->tr();
            $tr->td($key);
            $tr->td($value);
        }
        
        return $table; 
    }
}