<?php

namespace Gems\Snippets\Export;

use Gems\Export;
use Gems\Export\ExportInterface;
use Gems\Form;
use Gems\Html;
use Gems\Loader;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Snippets\FormSnippetAbstract;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\Task\TaskRunnerBatch;
use Mezzio\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessageTrait;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\SnippetsLoader\SnippetException;
use Zalt\SnippetsLoader\SnippetOptions;

class ExportFormSnippet extends FormSnippetAbstract
{
    use MessageTrait;

    protected ExportInterface $currentExport;

    /**
     *
     * @var \Gems\Export
     */
    protected Export $export;

    protected MetaModellerInterface $model;

    protected bool $processed = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        Loader $loader,
        protected ExportAction $exportAction,
        private readonly SessionInterface $session,
        private readonly ProjectOverloader $overLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);

        $this->export    = $loader->getExport();
        $this->saveLabel = $this->_('Export');
    }

    protected function addFormElements(mixed $form)
    {
        if (! $form instanceof Form) {
            throw new SnippetException(sprintf("Incorrect form type %s, expected a \gems\Form form!"), get_class($form));
        }

        $form->setAutoSubmit(\MUtil\Html::attrib('href', array('action' => $this->requestInfo->getCurrentAction())), 'export-form', true);

        $element = $form->createElement('select', 'type', [
            'label' => $this->_('Export to'),
            'multiOptions' => $this->export->getExportClasses(),
            'class' => 'autosubmit'
        ]);
        $form->addElement($element);

        $exportFormElements = $this->currentExport->getFormElements($form, $this->formData);

        if ($exportFormElements) {
            $form->addElements($exportFormElements);
        }
    }

    protected function createForm($options = null)
    {
        $options['id'] = 'exportOptionsForm';
        $options['class'] = 'form-horizontal';
        $options['data-autosubmit-inplace'] = true;

        return parent::createForm($options);
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues(): array
    {
        $defaults[$this->currentExport->getName()] = $this->currentExport->getDefaultFormValues();
        $defaults['type'] = $this->export->getDefaultExportClass();

        return $defaults;
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

    public function hasHtmlOutput(): bool
    {
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
        $currentType = $this->requestInfo->getParam('type', $this->export->getDefaultExportClass());
        $this->exportAction->batch = new TaskRunnerBatch('export_data_' . $this->model->getName(), $this->overLoader, $this->session);

        $this->currentExport = $this->export->getExport($currentType, null, $this->exportAction->batch);

        if ($this->isPost()) {
            $this->formData = $this->requestInfo->getRequestPostParams() + $this->getDefaultFormValues();
            return $this->formData;
        }

        $this->formData = $this->getDefaultFormValues() + $this->requestInfo->getRequestPostParams();
        return $this->formData;
    }

    protected function setAfterSaveRoute()
    {
        $this->processed = true;
    }
}
