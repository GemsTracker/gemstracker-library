<?php

declare(strict_types=1);

namespace Gems\Snippets\Export;

use Gems\Export\Db\DbExportRepository;
use Gems\Export\Db\FileExportDownloadModel;
use Gems\Export\Export;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\SnippetsActions\Export\ExportAction;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class ExportDownloadStepSnippet extends ExportDownloadSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        FileExportDownloadModel $fileExportDownloadModel,
        DbExportRepository $dbExportRepository,
        Export $export,
        CurrentUserRepository $currentUserRepository,
        protected readonly ExportAction $exportAction,

    ) {
        parent::__construct(
            $snippetOptions,
            $requestInfo,
            $menuHelper,
            $translate,
            $fileExportDownloadModel,
            $dbExportRepository,
            $export,
            $currentUserRepository,
        );
    }

    public function hasHtmlOutput(): bool
    {
        if (($this->exportAction->step !== ExportAction::STEP_DOWNLOAD) || (! isset($this->exportAction->batch))) {
            return false;
        }
        return parent::hasHtmlOutput();
    }
}