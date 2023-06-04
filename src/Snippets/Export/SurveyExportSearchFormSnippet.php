<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
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
 * @since      Class available since version 1.8.0
 */
class SurveyExportSearchFormSnippet extends SurveyExportSearchFormSnippetAbstract
{
	/**
     * Returns start elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getSurveySelectElements(array $data)
    {
     	$dbLookup = $this->util->getDbLookup();

        // get the current selections
        $roundDescr = isset($data['gto_round_description']) ? $data['gto_round_description'] : null;
        $surveyId   = isset($data['gto_id_survey']) ? $data['gto_id_survey'] : null;
        $trackId    = isset($data['gto_id_track']) ? $data['gto_id_track'] : null;

        // Get the selection data
        $rounds = $dbLookup->getRoundsForExport($trackId, $surveyId);
        $surveys = $dbLookup->getSurveysForExport($trackId, $roundDescr);
        if ($surveyId) {
            $tracks = $this->util->getTrackData()->getTracksBySurvey($surveyId);
        } else {
            $tracks = $this->util->getTrackData()->getTracksForOrgs($this->currentUser->getRespondentOrganizations());
        }

        $elements['gto_id_survey'] = $this->_createSelectElement(
            'gto_id_survey',
            $surveys,
            $this->_('(select a survey)')
            );
        $elements['gto_id_track'] = $this->_createSelectElement(
                'gto_id_track',
                $tracks,
                $this->_('(select a track)')
                );
       	$elements['gto_round_description'] = $this->_createSelectElement(
                'gto_round_description',
                [parent::NoRound => $this->_('No round description')] + $rounds,
                $this->_('(select a round)')
                );

        foreach ($elements as $element) {
            if ($element instanceof \Zend_Form_Element_Multi) {
                $element->setAttrib('onchange', 'this.form.submit();');
            }
        }

        return $elements;
   }
}