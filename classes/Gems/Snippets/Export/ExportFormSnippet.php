<?php

namespace Gems\Snippets\Export;

use MUtil\Snippets\SnippetAbstract;

class ExportFormSnippet extends \MUtil_Snippets_SnippetAbstract
{
	public $loader;

    public $request;

	public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $export = $this->loader->getExport();
        $exportTypes = $export->getExportClasses();

        if (\MUtil_Bootstrap::enabled()) {
            $form = new \Gems_Form(array('id' => 'exportOptionsForm', 'class' => 'form-horizontal'));
        } else {
            $form = new \Gems_Form_TableForm();
        }

        $elements = array();

        $element = $form->createElement('select', 'type');
        $element->setLabel($this->_('Export to'))
                ->setMultiOptions($exportTypes);
        $elements['type'] = $element;

        $form->addElements($elements);

        foreach($exportTypes as $exportClassName=>$exportName) {

            $exportClass = $export->getExport($exportClassName);
            $exportFormElements = $exportClass->getFormElements($form, $data);

            if ($exportFormElements) {
            	$exportFormElementNames = array();
            	foreach($exportFormElements as $formElement) {
            		$name = $formElement->getName();
            		$exportFormElementNames[] = $newName = strtolower($exportClassName) . $name;
            		$formElement->setName($newName);

            	}

                //$elements = array_merge($elements, $exportFormElements);
                $displayGroup = $form->addDisplayGroup($exportFormElements, $exportClassName, array('class' => 'export-class-group export-class-'.strtolower($exportClassName).' hidden', 'legend' => sprintf($this->_('%s options'), $exportName)));
                //$form->addElements($exportFormElements);
            }
        }

        $element = $form->createElement('submit', 'export_submit', array('label' => $this->_('Export')));
        $form->addElement($element);

        return $form;
    }
}