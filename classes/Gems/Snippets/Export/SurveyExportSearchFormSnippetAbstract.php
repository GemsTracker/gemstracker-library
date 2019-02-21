<?php

/**
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
abstract class SurveyExportSearchFormSnippetAbstract extends \Gems_Snippets_AutosearchFormSnippet
{
	/**
     * Defines the value used for 'no round description'
     *
     * It this value collides with a used round description, change it to something else
     */
    const NoRound = '-1';

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Export_ModelSource_ExportModelSourceAbstract
     */
    protected $exportModelSource;

	/**
     *
     * @var \Gems_Loader
     */
    protected $loader;

	/**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $elements = $this->getSurveySelectElements($data);

        $elements[] = null;

        $organizations = $this->currentUser->getRespondentOrganizations();
        if (count($organizations) > 1) {
            $elements[] = $this->_createMultiCheckBoxElements('gto_id_organization', $organizations);

            $elements[] = null;
        }

        $dates = array(
            'gr2t_start_date'     => $this->_('Track start'),
            'gr2t_end_date'       => $this->_('Track end'),
            'gto_valid_from'      => $this->_('Valid from'),
            'gto_valid_until'     => $this->_('Valid until'),
            'gto_start_time'      => $this->_('Start date'),
            'gto_completion_time' => $this->_('Completion date'),
            );
        // $dates = 'gto_valid_from';
        $this->_addPeriodSelectors($elements, $dates, 'gto_valid_from');

        $elements[] = null;

        $element = $this->form->createElement('textarea', 'ids');
        $element->setLabel($this->_('Respondent id\'s'))
            ->setAttrib('cols', 60)
            ->setAttrib('rows', 4)
            ->setDescription($this->_("Not respondent nr, but respondent id as exported here. Separate multiple id's with , or ;"));
        $elements['ids'] = $element;

        $elements[] = null;

        $elements[] = $this->_('Output');
        $elements['incomplete'] = $this->_createCheckboxElement(
                'incomplete',
                $this->_('Include incomplete surveys'),
                $this->_('Include surveys that have been started but have not been checked as completed')
                );
        $elements['column_identifiers'] = $this->_createCheckboxElement(
                'column_identifiers',
                $this->_('Column Identifiers'),
                $this->_('Prefix the column labels with an identifier. (A) Answers, (TF) Trackfields, (D) Description')
                );
        $elements['show_parent'] = $this->_createCheckboxElement(
                'show_parent',
                $this->_('Show parent'),
                $this->_('Show the parent column even if it doesn\'t have answers')
                );
        $elements['prefix_child'] = $this->_createCheckboxElement(
                'prefix_child',
                $this->_('Prefix child'),
                $this->_('Prefix the child column labels with parent question label')
                );
        $elements[] = null;

        $extraFields = $this->getExtraFieldElements($data);

        if ($extraFields) {
            $elements[] = $this->_('Add to export');
            $elements = $elements + $extraFields;
            $elements[] = null;
        }

        return $elements;
    }

	/**
     * Returns extra field elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getExtraFieldElements(array $data)
    {
        return $this->exportModelSource->getExtraDataFormElements($this->form, $data);
    }

	/**
     * Returns start elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    abstract protected function getSurveySelectElements(array $data);
}