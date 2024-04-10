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
use Gems\Export\Exception\ExportException;
use Gems\Export\Export;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\SnippetsActions\Export\ExportAction;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessageTrait;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Snippets\ModelSnippetAbstract;
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
class ExportDownloadSnippet extends ModelSnippetAbstract
{
    use MessageTrait;

    protected int $currentUserId;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    protected ?ResponseInterface $response;

    protected bool $sensitiveData = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected ExportAction $exportAction,
        protected ProjectOverloader $overLoader,
        protected SessionInterface $session,
        protected readonly DbExportRepository $dbExportRepository,
        readonly CurrentUserRepository $currentUserRepository,
        protected readonly Export $export,
    ) {
        $this->messenger = $messenger;
        $this->currentUserId = $this->currentUserRepository->getCurrentUserId();

        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createModel(): FullDataInterface
    {
        return $this->model;
    }

    public function getResponse(): ?ResponseInterface
    {
        if (isset($this->response)) {
            return $this->response;
        }

        return null;
    }

    public function getHtmlOutput()
    {
        $this->addMessage($this->_('Download no longer available.'));

        return Html::actionLink([$this->requestInfo->getBasePath()] + ['step' => ExportAction::STEP_RESET], $this->_('Reset'));
    }

    public function hasHtmlOutput(): bool
    {
        if (($this->exportAction->step !== ExportAction::STEP_DOWNLOAD) || (! isset($this->exportAction->batch))) {
            return false;
        }
        // $batch = new TaskRunnerBatch('export_data_' . $this->model->getName(), $this->overLoader, $this->session);
        $batch = $this->exportAction->batch;
        $exportId = $batch->getSessionVariable('exportId');

        try {
            $this->response = $this->dbExportRepository->exportFile($exportId, $this->currentUserId, $this->export->streamOnly($this->sensitiveData));
            if ($this->response === null) {
                die;
            }
            return false;
        } catch(ExportException) {

        }

        return true;
    }
}
