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
     * @var SessionInterface
     */
    //protected $session;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

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
        if (!$batch->count()) {
            $batch->minimalStepDurationMs = 2000;
            $batch->finishUrl = $this->routeHelper->getRouteUrl('setup.codes.mail-code.export', ['step' => 'download']);

            $batch->setSessionVariable('files', []);

            if (! isset($post['type'])) {
                // Export type is needed, use most basic type
                $post['type'] = 'CsvExport';
            }
            $batch->addTask('Export\\ExportCommand', $post['type'], 'addExport', $post);
            $batch->addTask('addTask', 'Export\\ExportCommand', $post['type'], 'finalizeFiles', $post);

            $export = $this->loader->getExport()->getExport($post['type'], $this->session);
            if ($snippet = $export->getHelpSnippet()) {
                $this->addSnippet($snippet);
            }

            $batch->autoStart = true;
        }

        $title = $this->_('Export');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_(
                'Regel 1...'
            ),
            $this->_(
                'Regel 2...'
            ),
        ]);
        return $batchRunner->getResponse($this->request);
    }

    public function hasHtmlOutput(): bool
    {
        return false;
    }
}
