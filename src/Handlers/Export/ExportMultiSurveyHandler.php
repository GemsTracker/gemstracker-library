<?php

namespace Gems\Handlers\Export;

use Gems\Snippets\Export\MultiSurveysSearchFormSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;

class ExportMultiSurveyHandler extends ExportSurveyHandler
{
    protected array $exportStartSnippets = [
        ContentTitleSnippet::class,
        MultiSurveysSearchFormSnippet::class,
    ];

    protected bool $multi = true;

    public function getIndexTitle(): string
    {
        return $this->_('Export multiple surveys');
    }

    public function getTopic(int $count = 1): string
    {
        return $this->plural('export multiple surveys', 'exports of multiple surveys', $count);
    }
}