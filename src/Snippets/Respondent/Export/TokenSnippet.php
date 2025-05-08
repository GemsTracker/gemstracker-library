<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Respondent\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Respondent\Export;

use Gems\Tracker\Token;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Sequence;
use Zalt\Ra\Ra;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Respondent\Export
 * @since      Class available since version 1.0
 */
class TokenSnippet extends TranslatableSnippetAbstract
{
    protected bool $groupSurveys = false;

    protected Token $token;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly SnippetLoader $snippetLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->groupSurveys = (bool) $this->requestInfo->getParam('group', false);
    }

    public function addSurveyHeader(Sequence $html): void
    {
        $html->div($this->token->getSurveyName(), array('class'=>'surveyTitle'), ' ');
        $html->div($this->token->getRoundDescription(), array('class'=>'roundDescription', 'renderClosingTag'=>true));
    }

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $params = array(
            'token'          => $this->token,
            'tokenId'        => $this->token->getTokenId(),
            'showHeaders'    => false,
            'showButtons'    => false,
            'showSelected'   => false,
            'showTakeButton' => false,
            'htmlExport'     => true,
            'grouped'        => $this->groupSurveys);

        $snippets = $this->token->getAnswerSnippetNames();

        if (!is_array($snippets)) {
            $snippets = array($snippets);
        }

        list($snippets, $snippetParams) = Ra::keySplit($snippets);
        $params = $params + $snippetParams;

        $this->addSurveyHeader($html);

        foreach($snippets as $snippet) {
            $html->append($this->snippetLoader->getSnippet($snippet, $params)->getHtmlOutput());
        }

        $html->br();

        return $html;
    }
}