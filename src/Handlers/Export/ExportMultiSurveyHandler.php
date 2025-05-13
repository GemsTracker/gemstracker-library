<?php

namespace Gems\Handlers\Export;

use Gems\Export\Db\AnswerModelContainer;
use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\PlaceholderModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\Export\MultiSurveysSearchFormSnippet;
use Gems\Snippets\Export\SurveyExportSearchFormSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Export\ExportAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class ExportMultiSurveyHandler extends ExportSurveyHandler
{
    protected array $exportStartSnippets = [
        ContentTitleSnippet::class,
        MultiSurveysSearchFormSnippet::class,
    ];

    protected bool $multi = true;
}