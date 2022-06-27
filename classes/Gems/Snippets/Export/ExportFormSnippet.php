<?php

namespace Gems\Snippets\Export;

use MUtil\Snippets\SnippetAbstract;

class ExportFormSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Export
     */
    protected $export;

    /**
     * Should be set to the available export classes
     * 
     * @var array
     */
    protected $exportClasses;
    
    public $request;

    public function afterRegistry() {
        parent::afterRegistry();
        $this->export = $this->loader->getExport();

        if (!isset($this->exportClasses)) {
            $this->exportClasses = $this->export->getExportClasses();
        }
    }

    public function getHtmlOutput(\Zend_View_Abstract $view) {
        $post = $this->request->getPost();

        if (isset($post['type'])) {
            $currentType = $post['type'];
        } else {
            reset($this->exportClasses);
            $currentType = key($this->exportClasses);
        }

        $form = new \Gems_Form(array('id' => 'exportOptionsForm', 'class' => 'form-horizontal'));

        $url = $view->url() . '/step/batch';
        $form->setAction($url);

        $elements = array();

        $elements['type'] = $form->createElement('select', 'type', array('label' => $this->_('Export to'), 'multiOptions' => $this->exportClasses, 'class' => 'autosubmit'));

        $form->addElements($elements);

        $exportClass        = $this->export->getExport($currentType);
        $exportName         = $exportClass->getName();
        $exportFormElements = $exportClass->getFormElements($form, $data);

        if ($exportFormElements) {
            $exportFormElements['firstCheck'] = $form->createElement('hidden', $currentType)->setBelongsTo($currentType);
            $form->addElements($exportFormElements);
        }

        if (!isset($post[$currentType])) {
            $post[$exportName] = $exportClass->getDefaultFormValues();
        }

        $element = $form->createElement('submit', 'export_submit', array('label' => $this->_('Export')));
        $form->addElement($element);

        if ($post) {
            $form->populate($post);
        }

        $container = \MUtil_Html::div(array('id' => 'export-form'));
        $container->append($form);
        $form->setAttrib('id', 'autosubmit');
        $form->setAutoSubmit(\MUtil_Html::attrib('href', array('action' => $this->request->getActionName())), 'export-form', true);

        return $container;
    }
}
