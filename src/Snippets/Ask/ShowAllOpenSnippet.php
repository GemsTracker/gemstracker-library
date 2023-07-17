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
use Gems\Menu\RouteHelper;
use Gems\Tracker;
use Gems\Tracker\Snippets\ShowTokenLoopAbstract;
use Gems\Tracker\Token;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
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
        RouteHelper $routeHelper,
        CommunicationRepository $communicationRepository,
        Translator $translator,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $routeHelper, $communicationRepository, $translator);
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
            $where .= sprintf(" OR gto_completion_time >= %s", $filterTime->format('Y-m-d H:i:s'));
        }

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
            $html->pInfo(sprintf($this->translator->_('Thank you for answering the "%s" survey.'), $this->token->getSurvey()->getExternalName()));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->raw($welcome);
            }
        }

        $tokens = $this->getDisplayTokens();
        if ($tokens) {
            $lastRound = false;
            $lastTrack = false;
            $open      = 0;
            $pStart    = $html->pInfo();

            foreach ($tokens as $token) {
                if ($token instanceof Token) {
                    if ($token->getTrackEngine()->getExternalName() !== $lastTrack) {
                        $lastTrack = $token->getTrackEngine()->getExternalName();

                        $div = $html->div();
                        $div->class = 'askTrack';
                        $div->append($this->translator->_('Track'));
                        $div->append(' ');
                        $div->strong($lastTrack);
                        if ($token->getRespondentTrack()->getFieldsInfo()) {
                            $div->small(sprintf($this->translator->_(' (%s)'), $token->getRespondentTrack()->getFieldsInfo()));
                        }
                    }

                    if ($token->getRoundDescription() && ($token->getRoundDescription() !== $lastRound)) {
                        $lastRound = $token->getRoundDescription();
                        $div = $html->div();
                        $div->class = 'askRound';
                        $div->strong(sprintf($this->translator->_('Round: %s'), $lastRound));
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

                        $button = Html::actionLink($this->getTokenUrl($token), $survey->getExternalName());
                        $div->append($button);
                        $div->append(' ');
                        $div->append($this->formatDuration($survey->getDuration()));
                        $div->append($this->formatUntil($token->getValidUntil()));
                    }
                }
            }
            if ($open) {
                $pStart->append($this->translator->plural('Please answer the open survey.', 'Please answer the open surveys.', $open));
            } else {
                $html->pInfo($this->translator->_('Thank you for answering all open surveys.'));
            }
        } else {
            $html->pInfo($this->translator->_('There are no surveys to show for this token.'));
        }

        if ($sig = $org->getSignature()) {
            $p = $html->pInfo();
            $p->br();
            $p->raw($sig);
        }
        return $html;
    }
}
