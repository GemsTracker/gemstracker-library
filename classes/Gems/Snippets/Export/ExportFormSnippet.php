<?php

namespace Gems\Snippets\Export;

use MUtil\Snippets\SnippetAbstract;

class ExportFormSnippet extends \MUtil_Snippets_SnippetAbstract
{
	public $loader;

    public $request;

	public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $post = $this->request->getPost();

        $export = $this->loader->getExport();
        $exportTypes = $export->getExportClasses();

        if (isset($post['type'])) {
            $currentType = $post['type'];
        } else {
            reset($exportTypes);
            $currentType = key($exportTypes);
        }

        if (\MUtil_Bootstrap::enabled()) {
            $form = new \Gems_Form(array('id' => 'exportOptionsForm', 'class' => 'form-horizontal'));
        } else {
            $form = new \Gems_Form_TableForm();
        }

        $url = $view->url() . '/step/batch';
        $form->setAction($url);

        $elements = array();

        $elements['type'] = $form->createElement('select', 'type', array('label' => $this->_('Export to'), 'multiOptions' => $exportTypes, 'class' => 'autosubmit'));

        $form->addElements($elements);

        $exportClass = $export->getExport($currentType);
        $exportName = $exportClass->getName();        
        $exportFormElements = $exportClass->getFormElements($form, $data);        
        
        if ($exportFormElements) {
            $exportFormElements['firstCheck'] = $form->createElement('hidden', $currentType);
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
        $form->setAutoSubmit(\MUtil_Html::attrib('href', array('action' => $this->request->getActionName(), 'RouteReset' => true)), 'export-form', true);

        return $container;
    }
}