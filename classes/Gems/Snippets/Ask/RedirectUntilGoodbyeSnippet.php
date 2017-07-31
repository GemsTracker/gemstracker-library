<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ShowFirstOpenSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

namespace Gems\Snippets\Ask;

/**
 * Loops through all open surveys and then shows an endmessage
 *
 * Works using $project->getAskDelay()
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class RedirectUntilGoodbyeSnippet extends \Gems_Tracker_Snippets_ShowTokenLoopAbstract
{
    /**
     * Optional, calculated from $token
     *
     * @var \Gems_Tracker_Token
     */
    protected $currentToken;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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
        $messages = false;

        if ($this->wasAnswered) {
            $this->currentToken = $this->token->getNextUnansweredToken();
        } else {
            $validator = $this->loader->getTracker()->getTokenValidator();

            if ($validator->isValid($this->token->getTokenId())) {
                $this->currentToken = $this->token;
            } else {
                $messages = $validator->getMessages();
                $this->currentToken = $this->token->getNextUnansweredToken();
            }
        }

        if ($this->currentToken instanceof \Gems_Tracker_Token) {
            $href = $this->getTokenHref($this->currentToken);
            $url  = $href->render($this->view);

            // Redirect at once
            header('Location: ' . $url);
            exit();
        }

        // After the header() so that the patient does not see the messages after answering surveys
        if ($messages) {
            $this->addMessage($messages);
        }

        $org  = $this->token->getOrganization();
        $html = $this->getHtmlSequence();

        $html->h3($this->_('Token'));
        $html->pInfo(sprintf($this->_('Thank you %s,'), $this->token->getRespondentName()));

        if ($welcome = $org->getWelcome()) {
            $html->pInfo()->raw(\MUtil_Markup::render($this->_($welcome), 'Bbcode', 'Html'));
        }

        $p = $html->pInfo()->spaced();
        if ($this->wasAnswered) {
            $p->append($this->_('Thanks for answering our questions.'));
        } elseif (! $this->token->isCurrentlyValid()) {
            if ($this->token->isExpired()) {
                $this->addMessage($this->_('This survey has expired. You can no longer answer it.'));
            } else {
                $this->addMessage($this->_('This survey is no longer valid.'));
            }
        }
        $p->append($this->_('We have no further questions for you at the moment.'));
        $p->append($this->_('We appreciate your cooperation very much.'));

        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw(\MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
        }

        /*
        $html->br();

        $href = array($this->request->getActionKey() => 'index', \MUtil_Model::REQUEST_ID => null);
        $buttonDiv = $html->buttonDiv(array('class' => 'centerAlign'));
        $buttonDiv->actionLink($href, $this->_('OK'));
        // */

        return $html;
    }
}
