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

use Gems\Communication\CommunicationRepository;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Tracker;
use Gems\Tracker\Snippets\ShowTokenLoopAbstract;
use Gems\Tracker\Token;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\AElement;
use Zalt\Html\HtmlInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.4
 */
class ShowAllOpenSnippet extends ShowTokenLoopAbstract
{
    /**
     * Show completed surveys answered in last X hours
     *
     * @var int
     */
    protected int $lookbackInHours = 24;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translator,
        CommunicationRepository $communicationRepository,
        MenuSnippetHelper $menuSnippetHelper,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translator, $communicationRepository, $menuSnippetHelper);
    }

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
            $where .= sprintf("OR DATE_ADD(gto_completion_time, INTERVAL %d HOUR) >= CURRENT_TIMESTAMP", $this->lookbackInHours);
        }

        // We always look back from the entered token
        if ($this->token->isCompleted()) {
            $filterTime = $this->token->getCompletionTime()->sub(new \DateInterval('PT1H'));
            $where .= sprintf(" OR gto_completion_time >= '%s'", $filterTime->format('Y-m-d H:i:s'));
        }

        // Ensure correct precedence of OR operators in this part of the query.
        $where = "($where)";

        // Get the tokens
        $tokens = $this->token->getAllUnansweredTokens([$where]);
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
     * @return HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();
        $org  = $this->token->getOrganization();

        $html->h3($this->getHeaderLabel());
        $html->append($this->formatWelcome());

        if ($this->wasAnswered) {
            $html->p(sprintf($this->_('Thank you for answering the "%s" survey.'), $this->token->getSurvey()->getExternalName()), ['class' => 'info']);
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->p(['class' => 'info'])->raw(str_replace(["\n"], ["\n<br/>"], $welcome), );
            }
        }

        $tokens = $this->getDisplayTokens();
        if ($tokens) {
            $lastRound = false;
            $lastTrack = false;
            $open      = 0;
            $pStart    = $html->p(['class' => 'info']);

            foreach ($tokens as $token) {
                if ($token instanceof Token) {
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

                        $button = new AElement($this->getTokenUrl($token), $survey->getExternalName(), ['class' => 'actionlink btn']);
                        $div->append($button);
                        $div->append(' ');
                        $div->append($this->formatDuration($survey->getDuration()));
                        $div->append($this->formatUntil($token->getValidUntil()));
                    }
                }
            }
            if ($open) {
                $pStart->append($this->plural('Please answer the open survey.', 'Please answer the open surveys.', $open));
            } else {
                $html->p($this->_('Thank you for answering all open surveys.'), ['class' => 'info']);
            }
        } else {
            $html->p($this->_('There are no surveys to show for this token.'), ['class' => 'info']);
        }

        if ($sig = $org->getSignature()) {
            $p = $html->p(['class' => 'info']);
            $p->br();
            $p->append($sig);
        }
        return $html;
    }
}
