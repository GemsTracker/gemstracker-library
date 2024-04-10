<?php

namespace Gems\Snippets\Export;

use Gems\Audit\AuditLog;
use Gems\Export\Export;
use Gems\Export\Type\ExportInterface;
use Gems\Form;
use Gems\Html;
use Gems\Loader;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\FormSnippetAbstract;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\Task\ExportRunnerBatch;
use Mezzio\Session\SessionInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessageTrait;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\DataReaderGenericModelTrait;
use Zalt\Snippets\ModelSnippetTrait;
use Zalt\Snippets\ModelTextFilterTrait;
use Zalt\SnippetsLoader\SnippetException;
use Zalt\SnippetsLoader\SnippetOptions;

class ExportFormSnippet extends FormSnippetAbstract
{
    use DataReaderGenericModelTrait;
    use MessageTrait;
    use ModelSnippetTrait {
        ModelSnippetTrait::getSort as traitGetSort;
    }
    use ModelTextFilterTrait;

    protected ExportInterface $currentExport;

    protected bool $processed = false;

    protected bool $sensitiveData;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        Loader $loader,
        protected ExportAction $exportAction,
        private readonly SessionInterface $session,
        private readonly ProjectOverloader $overLoader,
        protected readonly Export $export,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
        $this->saveLabel = $this->_('Export');
    }

    protected function addFormElements(mixed $form)
    {
        if (! $form instanceof Form) {
            throw new SnippetException(sprintf("Incorrect form type %s, expected a \gems\Form form!"), get_class($form));
        }

        $form->setAutoSubmit(Html::attrib('href', array('action' => $this->requestInfo->getCurrentAction())), 'export-form', true);

        $element = $form->createElement('select', 'type', [
            'label' => $this->_('Export to'),
            'multiOptions' => $this->export->getExportClasses($this->sensitiveData),
            'class' => 'auto-submit'
        ]);
        $form->addElement($element);

        $exportFormElements = $this->currentExport->getFormElements($form, $this->formData);

        if ($exportFormElements) {
            $form->addElements($exportFormElements);
        }

        $this->addCsrf($this->csrfName, $this->csrfToken, $form);
    }

    protected function createForm($options = null)
    {
        $options['id'] = 'exportOptionsForm';
        $options['class'] = 'form-horizontal';
        $options['data-autosubmit-inplace'] = true;

        $form = parent::createForm($options);
        $form->setAction($this->requestInfo->getBasePath());

        return $form;
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues(): array
    {
        $defaults[$this->currentExport->getName()] = $this->currentExport->getDefaultFormValues();
        $defaults['type'] = $this->currentExport->getName();

        return $defaults;
    }

    public function getFilter(MetaModelInterface $metaModel) : array
    {
        if ($this->exportAction->batch->hasSessionVariable('modelFilter')) {
            return $this->exportAction->batch->getSessionVariable('modelFilter');
        }

        if (false !== $this->searchFilter) {
            $filter = $this->searchFilter;
        } else {
            $filter = $this->getRequestFilter($metaModel);
        }

        // Filter in request overrules same filter from extraFilter settings which again overrule fiwxedFilter settings
        // Sinc the arrays can contian numeric keys we use array_merge to include those from all filters
        $filter = array_merge($this->_fixedFilter, $this->extraFilter, $this->cleanUpFilter($filter, $metaModel));

        $output = $this->processTextFilter($filter, $metaModel, $this->searchFilter);
        $this->exportAction->batch->setSessionVariable('modelFilter', $output);
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getHtmlOutput()
    {
        $container = Html::div(array('id' => 'export-form'));
        $container->append(parent::getHtmlOutput());
        return $container;
    }

    public function getSort(MetaModelInterface $metaModel): array
    {
        if ($this->exportAction->batch->hasSessionVariable('modelSort')) {
            return $this->exportAction->batch->getSessionVariable('modelSort');
        }

        $output = $this->traitGetSort($metaModel);
        $this->exportAction->batch->setSessionVariable('modelSort', $output);
        return $output;
    }

    public function hasHtmlOutput(): bool
    {
        $this->exportAction->batch = new ExportRunnerBatch('export_data_' . $this->model->getName(), $this->overLoader, $this->session);

        if (ExportAction::STEP_RESET === $this->requestInfo->getParam('step')) {
            $this->exportAction->batch->reset();
        }

        if ($this->exportAction->step == ExportAction::STEP_FORM) {
            parent::hasHtmlOutput();

            if (! $this->processed) {
                return true;
            }
            $this->exportAction->step = ExportAction::STEP_BATCH;
        }
        return false;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        $currentType = $this->requestInfo->getParam('type', $this->export->getDefaultExportClass($this->sensitiveData));

        $this->currentExport = $this->export->getExport($currentType, null, $this->exportAction->batch);

        if ($this->isPost()) {
            $this->formData = $this->loadCsrfData() + $this->requestInfo->getRequestPostParams() + $this->getDefaultFormValues();
            return $this->formData;
        }

        $this->formData = $this->loadCsrfData() + $this->getDefaultFormValues() + $this->requestInfo->getRequestPostParams();
        return $this->formData;
    }

    protected function setAfterSaveRoute()
    {
        $this->processed = true;
    }
}
