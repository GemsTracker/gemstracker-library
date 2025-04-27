<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Respondent\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Respondent\Export;

use Gems\Repository\RespondentExportRepository;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Respondent\Export
 * @since      Class available since version 1.0
 */
class RespondentExportOutputSnippet extends TranslatableSnippetAbstract
{
    protected string $reportFooter = 'Respondent\\Export\\ReportFooterSnippet';

    protected string $reportHeader = 'Respondent\\Export\\ReportHeaderSnippet';

    protected string $respondentSnippet = 'Respondent\\Export\\RespondentHeaderSnippet';

    protected Respondent $respondent;

    protected string $trackSnippet = 'Respondent\\Export\\TrackSnippet';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly RespondentExportRepository $exportRepository,
        protected readonly SnippetLoader $snippetLoader,
        protected readonly Tracker $tracker,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->append($this->snippetLoader->getSnippet($this->reportHeader));
        $html->append($this->snippetLoader->getSnippet($this->respondentSnippet, ['respondent' => $this->respondent]));

        $respTracks = $this->tracker->getRespondentTracks($this->respondent->getId(), $this->respondent->getOrganizationId());
        foreach ($respTracks as $respTrack) {
            $html->raw($this->snippetLoader->getSnippet($this->trackSnippet, ['respondent' => $this->respondent, 'respondentTrack' => $respTrack])->render());
        }
        $html->append($this->snippetLoader->getSnippet($this->reportFooter));

        return $html;
    }

    public function getResponse(): ?ResponseInterface
    {
        if ($this->requestInfo->isPost()) {
            $filebasename = 'respondent-export-' . $this->respondent->getId();
            $this->exportRepository->getFileResponse($this->getHtmlOutput(), $filebasename, $this->requestInfo->getRequestPostParams());
        }
        return null;
    }


    public function hasHtmlOutput(): bool
    {
        return $this->requestInfo->isPost() && (! $this->exportRepository->hasFormDisplay($this->requestInfo->getRequestPostParams()));
    }
}