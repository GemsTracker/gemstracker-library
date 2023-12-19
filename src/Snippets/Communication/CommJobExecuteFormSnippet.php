<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Communication;

use Gems\Audit\AuditLog;
use Gems\Handlers\Setup\CommunicationActions\CommJobExecuteAllAction;
use Gems\Menu\MenuSnippetHelper;
use Gems\Task\Comm\CommJonRunnerBatch;
use Mezzio\Session\SessionInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @since      Class available since version 1.0
 */
class CommJobExecuteFormSnippet extends \Gems\Snippets\FormSnippetAbstract
{
    protected bool $processed = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected CommJobExecuteAllAction $commJobExecuteAllAction,
        protected readonly SessionInterface $session,
        protected readonly ProjectOverloader $overLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->saveLabel = $this->_('Execute!');
    }

    /**
     * @inheritDoc
     */
    protected function addFormElements(mixed $form)
    {
        $options = [
            'label' => $this->_('Execute'),
            'multiOptions' => [1 => $this->_('Preview'), 0 => $this->_('Send mails')],
            'separator' => ' ',
        ];

        /**
         * @var \Gems\Form $form
         */
        $element = $form->createElement('Radio', 'preview', $options);
        $form->addElement($element);
    }

    protected function getButtons(): array
    {
        $showLabel = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID) ? $this->_('Show') : $this->_('Cancel');

        return [
            ['url' => $this->menuHelper->getCurrentParentUrl(), 'label' => $showLabel],
            ];
    }

    protected function getDefaultFormValues(): array
    {
        return ['preview' => 1];
    }

    public function hasHtmlOutput(): bool
    {
        $jobId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID, 'all');

        if ($this->commJobExecuteAllAction->step == CommJobExecuteAllAction::STEP_FORM) {
            parent::hasHtmlOutput();

            if (! $this->processed) {
                return true;
            }
            $this->commJobExecuteAllAction->step = CommJobExecuteAllAction::STEP_BATCH;
        }
        $this->commJobExecuteAllAction->step = CommJobExecuteAllAction::STEP_BATCH;
        return false;
    }

    protected function setAfterSaveRoute()
    {
        $this->processed = true;
    }
}