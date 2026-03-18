<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Tracker\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Tracker\Export;

use Gems\Audit\AuditLog;
use Gems\Export\Export;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Export\ExportFormSnippet;
use Gems\SnippetsActions\Export\ExportSurveySettingsAction;
use Gems\Tracker\Export\ExportSurveySettings;
use Mezzio\Session\SessionInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Tracker\Export
 * @since      Class available since version 1.0
 */
class ExportSurveySettingsSnippet extends ExportFormSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        ExportSurveySettingsAction $exportAction,
        SessionInterface $session,
        ProjectOverloader $overLoader,
        ExportSurveySettings $export,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper, $exportAction, $session, $overLoader, $export);
    }
}