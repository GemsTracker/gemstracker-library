<?php

class Gems_Snippets_Export_AnswerAutosearchFormSnippet extends \Gems_Snippets_AutosearchFormSnippet
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
            $this->_('(Select a survey)')
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

        return $elements;
    }
}