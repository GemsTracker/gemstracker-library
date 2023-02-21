<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Ask;

use Zalt\Html\HtmlInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ResumeLaterSnippet extends \Gems\Tracker\Snippets\ShowTokenLoopAbstract
{
    /**
     * @var string
     */
    protected string $action = 'resume-later';
    
    /**
     * @param HtmlInterface $html
     */
    protected function addContinueNowButton(HtmlInterface $html)
    {
        $html->actionLink($this->getTokenHref($this->token), sprintf($this->translator->_('Click here to resume %s now'), $this->token->getSurvey()->getExternalName()));
    }

    /**
     * @return string Return the header for the screen
     */
    protected function getHeaderLabel()
    {
        return $this->_('Thank you for answering so far');
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        if ($this->checkContinueLinkClicked()) {
            // Continue later was clicked, handle the click
            return $this->continueClicked();
        }

        $html = $this->getHtmlSequence();

        $html->h3($this->getHeaderLabel());

        $html->pInfo($this->translator->_('You can resume later by clicking on the link in your current mail'));
        $html->pInfo($this->translator->_('or'));
        $this->addContinueNowButton($html);
        $this->addContinueLink($html);
        
        return $html;
    }    
}