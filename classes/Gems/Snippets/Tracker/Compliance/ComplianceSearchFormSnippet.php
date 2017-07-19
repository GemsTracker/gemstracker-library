<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_Compliance_ComplianceSearchFormSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Should the filler be shown?
     *
     * @var bool
     */
    protected $showFiller = true;

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
        $orgs = $this->currentUser->getRespondentOrganizations();

        $elements[] = $this->_createSelectElement(
                'gr2t_id_track',
                $this->util->getTrackData()->getTracksForOrgs($orgs),
                $this->_('(select a track)')
                );

        if (count($orgs) > 1) {
            $elements[] = $this->_createSelectElement('gr2t_id_organization', $orgs, $this->_('(all organizations)'));
            // $elements[] = $this->_createMultiCheckBoxElement('gr2t_id_organization', $orgs, $this->_('Toggle'), ' ');
        }

        $elements[] = null;

        $dates = array(
            'gr2t_start_date' => $this->_('Track start'),
            'gr2t_end_date'   => $this->_('Track end'),
            );
        // $dates = 'gto_valid_from';
        $this->_addPeriodSelectors($elements, $dates, 'gto_valid_from');

        $elements[] = null;

        if ($this->showFiller) {
            $sql = "SELECT DISTINCT ggp_id_group, ggp_name
                        FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                            INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                            INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                        WHERE ggp_group_active = 1 AND
                            gro_active=1 AND
                            gtr_active=1
                        ORDER BY ggp_name";
            $elements[] = $this->_createSelectElement('gsu_id_primary_group', $sql, $this->_('(all fillers)'));
        }

        return $elements;
    }

}
