<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Respondent\Export;

use Gems\Audit\AuditLog;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\RespondentExportRepository;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker\Respondent;
use Mezzio\Session\SessionInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @since      Class available since version 1.0
 */
class RespondentExportFormSnippet extends ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\Model\Respondent\RespondentModel
     */
    protected $model;

    /**
     * Optional
     *
     * @var ?\Gems\Tracker\Respondent
     */
    protected ?Respondent $respondent = null;

    protected string $respondentDetailsSnippet = 'Respondent\\RespondentDetailsSnippet';

    protected bool $show = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected readonly RespondentExportRepository $exportRepository,
        protected readonly SessionInterface $session,
        protected readonly SnippetLoader $snippetLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->createData = true;
        $this->saveLabel = $this->_('Export');
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        if ($changed) {
            $this->logChanges($changed);
        }
    }

    protected function createModel(): DataReaderInterface
    {
        return $this->exportRepository->getModel($this->session);
    }

    public function getHtmlOutput()
    {
        $seq = $this->getHtmlSequence();

        $params = [
            'model' => $this->model,
            'respondent' => $this->respondent,
        ];
        $details = $this->snippetLoader->getSnippet($this->respondentDetailsSnippet, $params);
        if ($details->hasHtmlOutput()) {
            $seq->append($details);
        }

        $seq->append(parent::getHtmlOutput());
        return $seq;
    }

    public function hasHtmlOutput(): bool
    {
        return parent::hasHtmlOutput() && $this->show;
    }

    protected function setAfterSaveRoute()
    {
        $this->show = $this->exportRepository->hasFormDisplay($this->formData);

        // Set not alt url
    }

}