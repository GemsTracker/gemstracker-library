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

use Gems\Html;

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
    protected function createSurveyElement(array $data): \Zend_Form_Element|array
    {
        $roundDescr = $data['gto_round_description'] ?? null;
        $trackId = $data['gto_id_track'] ?? null;

        $surveys = $this->getSurveysForExport($trackId, $roundDescr);

        return $this->_createSelectElement(
            'gto_id_survey',
            $surveys,
            $this->_('(select a survey)')
        );
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
    protected function getSurveySelectElements(array $data): array
    {
        // get the current selections
        $surveyId   = $data['gto_id_survey'] ?? null;
        $trackId    = $data['gto_id_track'] ?? null;

        // Get the selection data
        $rounds = $this->getRoundsForExport($trackId, $surveyId);
        if ($surveyId) {
            if (is_array($surveyId)) {
                $tracks = $this->trackDataRepository->getTracksBySurveys($surveyId);
            } else {
                $tracks = $this->trackDataRepository->getTracksBySurvey($surveyId);
            }

        } else {
            $tracks = $this->trackDataRepository->getTracksForOrgs($this->currentUserRepository->getCurrentUser()->getRespondentOrganizations());
        }

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
        $elements['gto_id_survey'] = $this->createSurveyElement($data);

        foreach ($elements as $element) {
            if ($element instanceof \Zend_Form_Element_Multi) {
                $element->setAttrib('class', 'auto-submit-force');
            }
        }

        return $elements;
   }
}