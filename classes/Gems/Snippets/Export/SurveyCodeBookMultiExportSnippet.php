<?php


namespace Gems\Snippets\Export;


class SurveyCodeBookMultiExportSnippet extends \Gems_Snippets_AutosearchFormSnippet
{

    public $loader;

    /**
     *
     * @var \Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of (possible nested) \Zend_Form_Element's or static text to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $dbLookup      = $this->util->getDbLookup();
        $surveys = $dbLookup->getSurveysForExport(null, null, true);

        $elements['gto_id_survey'] = $this->_createMultiCheckBoxElements('gto_id_survey', $surveys, '<br>');
        $elements[] = null;

        $elements = $elements + $this->getExportTypeElements($data);
        $elements[] = null;

        return $elements;
    }


    /**
     * Creates a submit button
     *
     * @return \Zend_Form_Element_Submit
     */
    protected function getAutoSearchSubmit()
    {
        return $this->form->createElement('submit', 'step', array('label' => $this->_('Export'), 'class' => 'button small'));
    }

    /**
     * Returns export field elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getExportTypeElements(array $data)
    {
        $export = $this->loader->getExport();
        $exportTypes = ['CodeBookExport' => 'Excel export'];

        if (isset($data['type'])) {
            $currentType = $data['type'];
        } else {
            reset($exportTypes);
            $currentType = key($exportTypes);
        }

        $elements['type_label'] = $this->_('Export to');

        $elements['type'] = $this->_createSelectElement( 'type', $exportTypes);
        $elements['type']->setAttrib('onchange', 'this.form.submit();');
        // $elements['step'] = $this->form->createElement('hidden', 'step');;

        $exportClass = $export->getExport($currentType);
        $exportName  = $exportClass->getName();
        $exportFormElements = $exportClass->getFormElements($this->form, $data);

        if ($exportFormElements) {
            $elements['firstCheck'] = $this->form->createElement('hidden', $currentType);
            foreach ($exportFormElements as $key => $formElement) {
                $elements['type_br_' . $key] = \MUtil_Html::create('br');
                $elements['type_el_' . $key] = $formElement;
            }
        }

//        if (!isset($data[$currentType])) {
//            $data[$exportName] = $exportClass->getDefaultFormValues();
//        }

        return $elements;
    }
}