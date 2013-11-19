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
 * @version    $Id: ShowAllOpenSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.4
 */
class Track_Token_ShowAllOpenSnippet extends Gems_Tracker_Snippets_ShowTokenLoopAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Show completed surveys answered in last X hours
     *
     * @var int
     */
    protected $lookbackInHours = 24;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

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
        $html    = $this->getHtmlSequence();
        $org     = $this->token->getOrganization();
        $tracker = $this->loader->getTracker();

        $html->h3($this->_('Token'));
        $p = $html->pInfo(sprintf($this->_('Welcome %s,'), $this->token->getRespondentName()));
        $p->br();
        $p->br();

        if ($this->wasAnswered) {
            $html->pInfo(sprintf($this->_('Thank you for answering the "%s" survey.'), $this->token->getSurveyName()));
            // $html->pInfo($this->_('Please click the button below to answer the next survey.'));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->raw(MUtil_Markup::render($this->_($welcome), 'Bbcode', 'Html'));
            }
            // $html->pInfo(sprintf($this->_('Please click the button below to answer the survey for token %s.'), strtoupper($this->token->getTokenId())));
        }

        // Only valid or answerd in the last
        $where = "(gto_completion_time IS NULL AND gto_valid_from <= CURRENT_TIMESTAMP AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP))";

        // Do we always look back
        if ($this->lookbackInHours) {
            $where .= $this->db->quoteInto("OR DATE_ADD(gto_completion_time, INTERVAL ? HOUR) >= CURRENT_TIMESTAMP", $this->lookbackInHours, Zend_Db::INT_TYPE);
        }

        // We always look back from the entered token
        if ($this->token->isCompleted()) {
            $filterTime = $this->token->getCompletionTime();
            $filterTime->subHour(1);
            $where .= $this->db->quoteInto(" OR gto_completion_time >= ?", $filterTime->toString('yyyy-MM-dd HH:mm:ss'));
        }

        // Get the tokens
        $select = $tracker->getTokenSelect();
        $select->andReceptionCodes()
                ->andRespondentTracks()
                ->andRounds()
                ->andSurveys()
                ->andTracks()
                ->forGroupId($this->token->getSurvey()->getGroupId())
                ->forRespondent($this->token->getRespondentId(), $this->token->getOrganizationId())
                ->onlySucces()
                ->forWhere($where)
                ->order('gtr_track_type')
                ->order('gtr_track_name')
                ->order('gr2t_track_info')
                ->order('gto_valid_until')
                ->order('gto_valid_from');

        if ($tokens = $select->fetchAll()) {
            $currentToken = $this->token->isCompleted() ? false : $this->token->getTokenId();
            $lastRound    = false;
            $lastTrack    = false;
            $open         = 0;
            $pStart       = $html->pInfo();

            foreach ($tokens as $row) {
                if (('T' == $row['gtr_track_type']) && ($row['gtr_track_name'] !== $lastTrack)) {
                    $lastTrack = $row['gtr_track_name'];

                    $div = $html->div();
                    $div->class = 'askTrack';
                    $div->append($this->_('Track'));
                    $div->append(' ');
                    $div->strong($row['gtr_track_name']);
                    if ($row['gr2t_track_info']) {
                        $div->small(sprintf($this->_(' (%s)'), $row['gr2t_track_info']));
                    }
                }

                if ($row['gto_round_description'] && ($row['gto_round_description'] !== $lastRound)) {
                    $lastRound = $row['gto_round_description'];
                    $div = $html->div();
                    $div->class = 'askRound';
                    $div->strong(sprintf($this->_('Round: %s'), $row['gto_round_description']));
                    $div->br();
                }
                $token = $tracker->getToken($row);

                $div = $html->div();
                $div->class = 'askSurvey';
                if ($token->isCompleted()) {
                    $div->actionDisabled($token->getSurveyName());
                    $div->append(' ');
                    $div->append($this->formatCompletion($token->getCompletionTime()));

                } else {
                    $open++;

                    $a = $div->actionLink($this->getTokenHref($token), $token->getSurveyName());
                    $div->append(' ');
                    $div->append($this->formatDuration($token->getSurvey()->getDuration()));
                    $div->append($this->formatUntil($token->getValidUntil()));

                    /*
                    if (false === $currentToken) {
                        $currentToken = $token->getTokenId();
                    }
                    if ($token->getTokenId() == $currentToken) {
                        $a->appendAttrib('class', 'currentRow');
                    } // */
                }
            }
            if ($open) {
                $pStart->append($this->plural('Please answer the open survey.', 'Please answer the open surveys.', $open));
            } else {
                $html->pInfo($this->_('Thank you for answering all open surveys.'));
            }
        } else {
            $html->pInfo($this->_('There are no surveys to show for this token.'));
        }

        if ($sig = $org->getSignature()) {
            $p = $html->pInfo();
            $p->br();
            $p->raw(MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
        }
        return $html;
    }
}
