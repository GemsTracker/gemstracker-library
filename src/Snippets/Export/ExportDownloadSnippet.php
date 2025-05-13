<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

use Gems\Export\Db\DbExportRepository;
use Gems\Export\Db\FileExportDownloadModel;
use Gems\Export\Exception\ExportException;
use Gems\Export\Export;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\ModelTableSnippet;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessageTrait;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 24-sep-2014 18:26:00
 */
class ExportDownloadSnippet extends ModelTableSnippet
{
    use MessageTrait;

    protected int $currentUserId;

    protected array $menuEditRoutes = ['delete'];

    protected ?ResponseInterface $response;

    protected bool $sensitiveData = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected readonly FileExportDownloadModel $fileExportDownloadModel,
        protected readonly DbExportRepository $dbExportRepository,
        protected readonly Export $export,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    protected function createModel(): DataReaderInterface
    {
        return $this->fileExportDownloadModel;
    }

    public function getResponse(): ?ResponseInterface
    {
        if (isset($this->response)) {
            return $this->response;
        }

        return null;
    }

    public function hasHtmlOutput(): bool
    {
        $currentExportId = $this->requestInfo->getParam('exportId');

        if ($currentExportId) {
            try {
                $this->response = $this->dbExportRepository->exportFile(
                    $currentExportId,
                    $this->currentUserId,
                    $this->export->streamOnly($this->sensitiveData)
                );
                if ($this->response === null) {
                    die;
                }
                return false;
            } catch (ExportException) {
            }
        }

        return parent::hasHtmlOutput();
    }

    protected function getShowUrls(TableBridge $bridge, array $keys): array
    {
        $queryParams = [
            ...$this->requestInfo->getRequestQueryParams(),
            'exportId' => $bridge->getLate('gfex_export_id'),
        ];

        $downloadLink = $this->menuHelper->getLateRouteUrl($this->menuHelper->getCurrentRoute(), $keys, $bridge, false, $queryParams);
        $downloadLink['label'] = $this->_('Download');

        return [
            $downloadLink,
        ];
    }
}
