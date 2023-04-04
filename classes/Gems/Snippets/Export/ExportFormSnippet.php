<?php

namespace Gems\Snippets\Export;

use Gems\Loader;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Task\TaskRunnerBatch;
use Mezzio\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetOptions;

class ExportFormSnippet extends \Zalt\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\Export
     */
    protected $export;

    /**
     * Should be set to the available export classes
     * 
     * @var array
     */
    protected $exportClasses;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        Loader $loader,
        private readonly MenuSnippetHelper $menuHelper,
        private readonly TranslatorInterface $translator,
        private readonly SessionInterface $session,
        private readonly ProjectOverloader $overLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo);

        $this->export = $loader->getExport();

        //if (!isset($this->exportClasses)) {
            $this->exportClasses = $this->export->getExportClasses();
        //}
    }

    public function getHtmlOutput() {
        $batch = new TaskRunnerBatch('export_data', $this->overLoader, $this->session);
        $batch->reset();

        $post = $this->requestInfo->getRequestPostParams();

        if (isset($post['type'])) {
            $currentType = $post['type'];
        } else {
            reset($this->exportClasses);
            $currentType = key($this->exportClasses);
        }

        $form = new \Gems\Form([
            'id' => 'exportOptionsForm',
            'class' => 'form-horizontal',
            'data-autosubmit-inplace' => true,
        ]);

        $url = $this->menuHelper->getRouteUrl('setup.codes.mail-code.export', ['step' => 'batch']);
        $form->setAction($url);

        $elements = array();

        $elements['type'] = $form->createElement('select', 'type', [
            'label' => $this->translator->trans('Export to'),
            'multiOptions' => $this->exportClasses,
            'class' => 'autosubmit'
        ]);

        $form->addElements($elements);

        $exportClass        = $this->export->getExport($currentType, null, $batch);
        $exportName         = $exportClass->getName();
        $exportFormElements = $exportClass->getFormElements($form, $data);

        if ($exportFormElements) {
            $exportFormElements['firstCheck'] = $form->createElement('hidden', $currentType)->setBelongsTo($currentType);
            $form->addElements($exportFormElements);
        }

        if (!isset($post[$currentType])) {
            $post[$exportName] = $exportClass->getDefaultFormValues();
        }

        $element = $form->createElement('submit', 'export_submit', array('label' => $this->translator->trans('Export')));
        $form->addElement($element);

        if ($post) {
            $form->populate($post);
        }

        $container = \Gems\Html::div(array('id' => 'export-form'));
        $container->append($form);
        //$form->setAttrib('id', 'autosubmit');
        $form->setAutoSubmit(\MUtil\Html::attrib('href', array('action' => $this->requestInfo->getCurrentAction())), 'export-form', true);

        return $container;
    }
}
