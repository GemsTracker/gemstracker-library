<?php


namespace Gems\Snippets\Export;


class SurveyCodeBookMultiExportSnippet extends MultiSurveysSearchFormSnippet
{
    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of (possible nested) \Zend_Form_Element's or static text to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data): array
    {
        $elements = $this->getSurveySelectElements($data);
        $elements[] = null;

        //$elements = $elements + $this->getExportTypeElements($data);
        $elements[] = null;

        return $elements;
    }
}
