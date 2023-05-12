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

use Gems\Batch\BatchRunnerLoader;
use Gems\Loader;
use Gems\Menu\MenuSnippetHelper;
use Gems\SnippetsActions\Export\ExportAction;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\UrlArrayAttribute;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
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
class ExportBatchSnippet extends ModelSnippetAbstract
{
    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

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
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createModel(): DataReaderInterface
    {
        return $this->model;
    }

    public function getResponse(): ?ResponseInterface
    {
        if (($this->exportAction->step !== ExportAction::STEP_BATCH) || (! isset($this->exportAction->batch))) {
            return null;
        }
        $batch = $this->exportAction->batch;
        $model = $this->getModel();

        $batch->setVariable('model', $model);
//        $batch->restartRedirectUrl = UrlArrayAttribute::toUrlString([$this->menuHelper->getRouteUrl($this->menuHelper->getCurrentRoute())] + ['step' => ExportAction::STEP_RESET]);
//        $batch->finishUrl = UrlArrayAttribute::toUrlString([$this->menuHelper->getRouteUrl($this->menuHelper->getCurrentRoute())] + ['step' => ExportAction::STEP_DOWNLOAD]);
        $batch->restartRedirectUrl = UrlArrayAttribute::toUrlString([$this->requestInfo->getBasePath()] + ['step' => ExportAction::STEP_RESET]);
        $batch->finishUrl = UrlArrayAttribute::toUrlString([$this->requestInfo->getBasePath()] + ['step' => ExportAction::STEP_DOWNLOAD]);

        $post = $this->requestInfo->getRequestPostParams();
        $jobInfo = [];

        if ($batch->isFinished()) {
            $this->exportAction->step = ExportAction::STEP_DOWNLOAD;
            return new RedirectResponse($batch->finishUrl);
        }

        if ($batch->hasSessionVariable('export_type')) {
            $type = $batch->getSessionVariable('export_type');
        } else {
            if (!isset($post['type'])) {
                return new RedirectResponse($batch->restartRedirectUrl);
            }

            $type = $post['type'];
            $batch->setSessionVariable('export_type', $type);

            if (!$batch->count()) {
                $batch->minimalStepDurationMs = 2000;

                $batch->setSessionVariable('files', []);

                $batch->addTask('Export\\ExportCommand', $type, 'addExport', $post);
                $batch->addTask('addTask', 'Export\\ExportCommand', $type, 'finalizeFiles', $post);

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
