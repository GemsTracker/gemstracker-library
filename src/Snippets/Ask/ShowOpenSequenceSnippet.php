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
class ShowOpenSequenceSnippet extends \Gems\Tracker\Snippets\ShowTokenLoopAbstract
{
    /**
     * Return a welcome greeting depending on showlastName switch
     *
     * @return string
     */
    public function formatWelcome()
    {
        if ($this->wasAnswered) {
            return $this->_('Welcome back,');
        } else {
            return $this->_('Welcome,');
        }
    }
    
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->p($this->formatWelcome(), ['class' => 'info']);
        
        if ($this->wasAnswered) {
            $nextToken = $this->token->getNextUnansweredToken();
            if ($nextToken) {
                $html->p(sprintf(
                    $this->_('Thank you for answering "%s".'),
                    $this->token->getSurvey()->getExternalName()),
                    ['class' => 'info']);

                $open = $this->getOpenCount();

                if ($open && $open > 1) {
                    $html->p($this->showTotal($open), ['class' => 'info']);
                }
                
                $html->p($this->showLink($nextToken), ['class' => 'info']);
            } else {
                $html->p(sprintf(
                    $this->_('Thank you for answering the surveys for "%s".'),
                    $this->token->getTrackEngine()->getExternalName()),
                    ['class' => 'info']
                );
            }
            
        } else {
            $html->p($this->showLink($this->token), ['class' => 'info']);
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
    
    public function showLink(\Gems\Tracker\Token $token)
    {
        $href   = $this->getTokenHref($token);
        $html   = $this->getHtmlSequence();
        $survey = $token->getSurvey();

        $html->append($this->_('Click on the link to answer the survey:'));
        $html->append(' ');
        $html->a($href, $survey->getExternalName(), ['class' => 'actionlink btn']);

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