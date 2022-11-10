<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Ask;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.4
 */
class ShowAllOpenSnippet extends \Gems\Tracker\Snippets\ShowTokenLoopAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Show completed surveys answered in last X hours
     *
     * @var int
     */
    protected $lookbackInHours = 24;

    /**
     * @return array tokenId => \Gems\Tracker\Token
     */
    protected function getDisplayTokens()
    {
        // Only valid
        $where = "(gto_completion_time IS NULL AND gto_valid_from <= CURRENT_TIMESTAMP AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP))";

        // Do we always look back
        if ($this->lookbackInHours) {
            //  or answered in the last X hours
            $where .= $this->db->quoteInto("OR DATE_ADD(gto_completion_time, INTERVAL ? HOUR) >= CURRENT_TIMESTAMP", $this->lookbackInHours, \Zend_Db::INT_TYPE);
        }

        // We always look back from the entered token
        if ($this->token->isCompleted()) {
            $filterTime = $this->token->getCompletionTime()->sub(new \DateInterval('PT1H'));
            $where .= $this->db->quoteInto(" OR gto_completion_time >= ?", $filterTime->format('Y-m-d H:i:s'));
        }

        // Get the tokens
        $tokens = $this->token->getAllUnansweredTokens($where);
        $output = [];

        foreach ($tokens as $tokenData) {
            $token = $this->tracker->getToken($tokenData);
            $output[$token->getTokenId()] = $token;
        }

        return $output;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $html = $this->getHtmlSequence();
        $org  = $this->token->getOrganization();

        $html->h3($this->getHeaderLabel());
        $html->append($this->formatWelcome());

        if ($this->wasAnswered) {
            $html->pInfo(sprintf($this->_('Thank you for answering the "%s" survey.'), $this->token->getSurvey()->getExternalName()));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->bbcode($welcome);
            }
        }

        $tokens = $this->getDisplayTokens();
        if ($tokens) {
            $lastRound = false;
            $lastTrack = false;
            $open      = 0;
            $pStart    = $html->pInfo();

            foreach ($tokens as $token) {
                if ($token instanceof \Gems\Tracker\Token) {
                    if ($token->getTrackEngine()->getExternalName() !== $lastTrack) {
                        $lastTrack = $token->getTrackEngine()->getExternalName();

                        $div = $html->div();
                        $div->class = 'askTrack';
                        $div->append($this->_('Track'));
                        $div->append(' ');
                        $div->strong($lastTrack);
                        if ($token->getRespondentTrack()->getFieldsInfo()) {
                            $div->small(sprintf($this->_(' (%s)'), $token->getRespondentTrack()->getFieldsInfo()));
                        }
                    }

                    if ($token->getRoundDescription() && ($token->getRoundDescription() !== $lastRound)) {
                        $lastRound = $token->getRoundDescription();
                        $div = $html->div();
                        $div->class = 'askRound';
                        $div->strong(sprintf($this->_('Round: %s'), $lastRound));
                        $div->br();
                    }

                    $div = $html->div();
                    $div->class = 'askSurvey';
                    $survey = $token->getSurvey();
                    if ($token->isCompleted()) {
                        $div->actionDisabled($survey->getExternalName());
                        $div->append(' ');
                        $div->append($this->formatCompletion($token->getCompletionTime()));

                    } else {
                        $open++;

                        $a = $div->actionLink($this->getTokenHref($token), $survey->getExternalName());
                        $div->append(' ');
                        $div->append($this->formatDuration($survey->getDuration()));
                        $div->append($this->formatUntil($token->getValidUntil()));
                    }
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
            $p->bbcode($sig);
        }
        return $html;
    }
}
