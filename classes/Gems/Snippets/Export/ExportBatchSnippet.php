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
use Gems\MenuNew\RouteHelper;
use Gems\Task\TaskRunnerBatch;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Loader\ProjectOverloader;
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
class ExportBatchSnippet extends ModelSnippetAbstract
{
    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected Loader $loader,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected ProjectOverloader $overLoader,
        protected RouteHelper $routeHelper,
        protected SessionInterface $session,
        protected ServerRequestInterface $request,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createModel(): FullDataInterface
    {
        return $this->model;
    }

    public function getResponse(): ?ResponseInterface
    {
        $batch = new TaskRunnerBatch('export_data', $this->overLoader, $this->session);
        $model = $this->getModel();

        $batch->setVariable('model', $model);
        $batch->restartRedirectUrl = $this->routeHelper->getRouteUrl('setup.codes.mail-code.export', ['step' => null]);
        $batch->finishUrl = $this->routeHelper->getRouteUrl('setup.codes.mail-code.export', ['step' => 'download']);

        $post = $this->requestInfo->getRequestPostParams();
        $jobInfo = [];

        if ($batch->isFinished()) {
            return new RedirectResponse($batch->restartRedirectUrl);
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

        $export = $this->loader->getExport()->getExport($type, null, $batch);

        if ($helpLines = $export->getHelpInfo()) {
            $jobInfo = [...$jobInfo, ...$helpLines];
        }

        $title = $this->_('Export');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo($jobInfo);

        return $batchRunner->getResponse($this->request);
    }

    public function hasHtmlOutput(): bool
    {
        return false;
    }
}
