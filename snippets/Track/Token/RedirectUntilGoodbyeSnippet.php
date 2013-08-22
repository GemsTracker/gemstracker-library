<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Tracker\Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ShowFirstOpenSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Loops through all open surveys and then shows an endmessage
 *
 * Works using $project->getAskDelay()
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Track_Token_RedirectUntilGoodbyeSnippet extends Gems_Tracker_Snippets_ShowTokenLoopAbstract
{
    /**
     * Optional, calculated from $token
     *
     * @var Gems_Tracker_Token
     */
    protected $currentToken;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        if ($this->wasAnswered) {
            $this->currentToken = $this->token->getNextUnansweredToken();
        } else {
            $this->currentToken = $this->token;
        }

        if ($this->currentToken instanceof Gems_Tracker_Token) {
            $href = $this->getTokenHref($this->currentToken);
            $url  = $href->render($this->view);

            // Redirect at once
            header('Location: ' . $url);
            exit();
        }

        $org  = $this->token->getOrganization();
        $html = $this->getHtmlSequence();

        $html->h3($this->_('Token'));
        $html->pInfo(sprintf($this->_('Thank you %s,'), $this->token->getRespondentName()));

        if ($welcome = $org->getWelcome()) {
            $html->pInfo()->raw(MUtil_Markup::render($this->_($welcome), 'Bbcode', 'Html'));
        }

        $p = $html->pInfo()->spaced();
        if ($this->wasAnswered) {
            $p->append($this->_('Thanks for answering our questions.'));
        }
        $p->append($this->_('We have no further questions for you at the moment.'));
        $p->append($this->_('We appreciate your cooperation very much.'));

        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw(MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
        }

        /*
        $html->br();

        $href = array($this->request->getActionKey() => 'index', MUtil_Model::REQUEST_ID => null);
        $buttonDiv = $html->buttonDiv(array('class' => 'centerAlign'));
        $buttonDiv->actionLink($href, $this->_('OK'));
        // */

        return $html;
    }
}
