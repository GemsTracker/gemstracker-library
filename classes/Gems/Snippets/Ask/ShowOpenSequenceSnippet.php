<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Ask;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ShowOpenSequenceSnippet extends \Gems_Tracker_Snippets_ShowTokenLoopAbstract
{
    /**
     * Return a welcome greeting depending on showlastName switch
     *
     * @return \MUtil_Html_HtmlElement
     */
    public function formatWelcome()
    {
        if ($this->wasAnswered) {
            return $this->_('Welcome back,'); 
        } else {
            return $this->_('Welcome,');
        }
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
        $html = $this->getHtmlSequence();
        $org = $this->token->getOrganization();
        
        $html->pInfo($this->formatWelcome());
        
        if ($this->wasAnswered) {
            $nextToken = $this->token->getNextUnansweredToken();
            if ($nextToken) {
                $html->pInfo(sprintf(
                                 $this->_('Thank you for your answering "%s",'),
                                 $this->token->getSurvey()->getExternalName()));

                $open = $this->getOpenCount();

                if ($open && $open > 1) {
                    $html->pInfo($this->showTotal($open));
                }
                
                $html->pInfo($this->showLink($nextToken));
            } else {
                $html->pInfo(sprintf(
                                 $this->_('Thank you for answering the surveys for "%s",'),
                                 $this->token->getTrackEngine()->getExternalName()));
            }
            
        } else {
            $html->pInfo($this->showLink($this->token));
        }
        
        return $html;
    }

    /**
     * @return int 
     * @throws \Zend_Db_Select_Exception
     */
    public function getOpenCount()
    {
        $open = $this->token->getTokenCountUnanswered();

        if ($this->token->isCompleted()) {
            return $open;
        }
        
        return $open + 1;
    }
    
    public function showLink(\Gems_Tracker_Token $token)
    {
        $href   = $this->getTokenHref($token);
        $html   = $this->getHtmlSequence();
        $survey = $token->getSurvey();

        $html->append($this->_('Click on the link to answer the survey:'));
        $html->append(' ');
        $html->actionLink($href, $survey->getExternalName());

        $html->br();
        $html->append($this->formatDuration($survey->getDuration()));
        $html->append($this->formatUntil($token->getValidUntil()));
        
        return $html;
    }
    public function showTotal($open)
    {
        return sprintf($this->_('There are open %d surveys'), $open);
    }
}