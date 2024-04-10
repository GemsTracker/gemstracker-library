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

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Batch\BatchRunnerLoader;
use Gems\Export\Db\ModelExportRepository;
use Gems\Loader;
use Gems\Menu\MenuSnippetHelper;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\Task\Export\InitDbExport;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\SnippetAbstract;
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
class ExportBatchSnippet extends SnippetAbstract
{
    /**
     *
     * @var DataReaderInterface
     */
    protected DataReaderInterface $model;

    public $modelApplyFunctions = [];

    protected string $formTitle = '';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected Loader $loader,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected ExportAction $exportAction,
        protected ProjectOverloader $overLoader,
        protected MenuSnippetHelper $menuHelper,
        protected SessionInterface $session,
        protected ServerRequestInterface $request,
        protected readonly ModelExportRepository $exportRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    public function getResponse(): ?ResponseInterface
    {
        if (($this->exportAction->step !== ExportAction::STEP_BATCH) || (! isset($this->exportAction->batch))) {
            return null;
        }
        $batch = $this->exportAction->batch;
        $model = $this->model;

        $batch->setVariable('model', $model);
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $post = $this->requestInfo->getRequestPostParams();
        $jobInfo = [];

        $currentUserId = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE);
        $batch->setVariable(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE, $currentUserId);

        if ($batch->isFinished()) {
            $this->exportAction->step = ExportAction::STEP_DOWNLOAD;
            return new RedirectResponse($batch->getDownloadUrl());
        }

        if ($batch->hasSessionVariable('export_type')) {
            $type = $batch->getSessionVariable('export_type');
        } else {
            if (!isset($post['type'])) {
                return new RedirectResponse($batch->restartRedirectUrl);
            }

            $type = $this->exportRepository->getExportTypeClassName($post['type']);
            $batch->setSessionVariable('export_type', $type);

            if (!$batch->count()) {
                $batch->minimalStepDurationMs = 2000;

                $batch->setSessionVariable('files', []);

                $modelFilter = [];
                if ($this->exportAction->batch->hasSessionVariable('modelFilter')) {
                    $modelFilter = $this->exportAction->batch->getSessionVariable('modelFilter');
                }

                $batch->addTask(InitDbExport::class, $this->model::class, $post, $modelFilter, $this->modelApplyFunctions, $type);
                //$batch->addTask('addTask', 'Export\\ExportCommand', $type, 'finalizeFiles', $post);

                $batch->autoStart = true;
            }
        }

        $batch->setSessionVariable('last_active_at', time());

        $export = $this->loader->getExport()->getExport($type, null, $batch);

        if ($helpLines = $export->getHelpInfo()) {
            $jobInfo = [...$jobInfo, ...$helpLines];
        }

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($this->formTitle);
        $batchRunner->setJobInfo($jobInfo);

        return $batchRunner->getResponse($this->request);
    }

    public function hasHtmlOutput(): bool
    {
        return false;
    }
}
