<?php


namespace Gems\Snippets\Export;


class SurveyCodeBookMultiExportSnippet extends MultiSurveysSearchFormSnippet
{

    public $loader;
    
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
        $elements = $this->getSurveySelectElements($data);        
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
     * Get the export classes to use
     * 
     * @param \Gems_Export $export
     * @return array
     */
    protected function getExportClasses(\Gems_Export $export)
    {
        return $export->getCodeBookExportClasses();
    }
}