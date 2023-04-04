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

use Gems\Task\TaskRunnerBatch;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
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

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected ProjectOverloader $overLoader,
        protected SessionInterface $session,
    ) {
        $this->messenger = $messenger;

        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createModel(): FullDataInterface
    {
        return $this->model;
    }

    public function getResponse(): ?ResponseInterface
    {
        $batch = new TaskRunnerBatch('export_data', $this->overLoader, $this->session);
        $file = $batch->getSessionVariable('file');
        if ($file && is_array($file) && is_array($file['headers'])) {
            $response = new \Laminas\Diactoros\Response\TextResponse(
                file_get_contents($file['file']),
                200,
                $file['headers']
            );

            // Now clean up the file
            unlink($file['file']);

            return $response;
        }

        $this->addMessage($this->_('Download no longer available.'));

        return null;
    }

    public function hasHtmlOutput(): bool
    {
        return false;
    }
}
