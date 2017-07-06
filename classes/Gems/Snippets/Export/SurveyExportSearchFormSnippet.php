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

        $elements['gto_id_survey'] = $this->_createSelectElement(
            'gto_id_survey',
            $dbLookup->getSurveysForExport(),
            $this->_('(select a survey)')
            );
        $elements['gto_id_survey']->setAttrib('onchange', 'this.form.submit();');

        if (isset($data['gto_id_survey'])) {
            $tracks = $this->util->getTrackData()->getTracksBySurvey($data['gto_id_survey']);
        } else {
            $tracks = $this->util->getTrackData()->getAllTracks();
        }
        $elements['gto_id_track'] = $this->_createSelectElement(
                'gto_id_track',
                $tracks,
                $this->_('(select a track)')
                );
        $elements['gto_id_track']->setAttrib('onchange', 'this.form.submit();');


        $rounds = $dbLookup->getRoundsForExport(
                isset($data['gto_id_track']) ? $data['gto_id_track'] : null,
                isset($data['gto_id_survey']) ? $data['gto_id_survey'] : null
                );
       	$elements[] = $this->_createSelectElement(
                'gto_round_description',
                [parent::NoRound => $this->_('No round description')] + $rounds,
                $this->_('(select a round)')
                );

        return $elements;
   }
}