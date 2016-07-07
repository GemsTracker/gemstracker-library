<?php

namespace Gems\Snippets\Export;

class AnswerAutosearchFormSnippet extends \Gems_Snippets_AutosearchFormSnippet
{

	/**
     * Defines the value used for 'no round description'
     *
     * It this value collides with a used round description, change it to something else
     */
    const NoRound = '-1';

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

    	$dbLookup      = $this->util->getDbLookup();
    	$translated    = $this->util->getTranslated();
    	$noRound       = array(self::NoRound => $this->_('No round description'));
    	$empty         = $translated->getEmptyDropdownArray();
    	$rounds        = $empty + $noRound + $dbLookup->getRoundsForExport();

        $surveys       = $dbLookup->getSurveysForExport();


        $elements[] = $this->_createSelectElement(
            'gto_id_survey',
            $surveys,
            $this->_('(select a survey)')
            );

        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $this->util->getTrackData()->getAllTracks(),
                $this->_('(select a track)')
                );

       	$elements[] = $this->_createSelectElement(
                'gto_round_description',
                $rounds,
                $this->_('(select a round)')
                );

        $orgs = $this->loader->getCurrentUser()->getRespondentOrganizations();
        if (count($orgs) > 1) {
            $elements[] = $this->_createSelectElement('gto_id_organization', $orgs, $this->_('(all organizations)'));
        }

        $elements[] = null;

        $dates = array(
            'gto_start_date' => $this->_('Track start'),
            'gto_end_date'   => $this->_('Track end'),
            'gto_valid_from'  => $this->_('Valid from'),
            'gto_valid_until' => $this->_('Valid until'),
            );
        // $dates = 'gto_valid_from';
        $this->_addPeriodSelectors($elements, $dates, 'gto_valid_from');

        $elements[] = null;

        $element = $this->form->createElement('checkbox', 'add_track_fields');
        $element->setLabel($this->_('Track fields'));
        $element->getDecorator('Label')->setOption('placement', \Zend_Form_Decorator_Abstract::APPEND);
        $element->setDescription($this->_('Add track fields to export'));
        $elements['tid_fields'] = $element;
        
        $elements[] = null;

        $element = $this->form->createElement('checkbox', 'column_identifiers');
        $element->setLabel($this->_('Column Identifiers'));
        $element->getDecorator('Label')->setOption('placement', \Zend_Form_Decorator_Abstract::APPEND);
        $element->setDescription($this->_('Prefix the column labels with an identifier. (A) Answers, (TF) Trackfields, (D) Description'));
        $elements[] = $element;

        $element = $this->form->createElement('checkbox', 'show_parent');
        $element->setLabel($this->_('Show parent'));
        $element->getDecorator('Label')->setOption('placement', \Zend_Form_Decorator_Abstract::APPEND);
        $element->setDescription($this->_('Show the parent column even if it doesn\'t have answers'));
        $elements[] = $element;

        $element = $this->form->createElement('checkbox', 'prefix_child');
        $element->setLabel($this->_('Prefix child'));
        $element->getDecorator('Label')->setOption('placement', \Zend_Form_Decorator_Abstract::APPEND);
        $element->setDescription($this->_('Prefix the child column labels with parent question label'));
        $elements[] = $element;

        $elements[] = null;

        return $elements;
    }
}