<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Tracker\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Tracker\Export;

use Gems\Export\Db\DbExportRepository;
use Gems\Export\Db\FileExportDownloadModel;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Export\ExportDownloadStepSnippet;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\Tracker\Export\ExportSurveySettings;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Tracker\Export
 * @since      Class available since version 1.0
 */
class ExportSurveySettingsDownloadStepSnippet extends ExportDownloadStepSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        FileExportDownloadModel $fileExportDownloadModel,
        DbExportRepository $dbExportRepository,
        ExportSurveySettings $export,
        CurrentUserRepository $currentUserRepository,
        ExportAction $exportAction)
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $fileExportDownloadModel, $dbExportRepository, $export, $currentUserRepository, $exportAction);
    }

}