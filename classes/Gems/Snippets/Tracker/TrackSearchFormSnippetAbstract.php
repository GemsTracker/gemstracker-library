<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class TrackSearchFormSnippetAbstract extends \Gems_Snippets_AutosearchFormSnippet
{

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;
    protected $singleTrackId = false;
    protected $trackFieldId  = false;

    /**
     * Add filler select to the elements array
     * 
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addFillerSelect(array &$elements, $data, $elementId = 'fillerfilter')
    {
        $elements[] = null;
        if (isset($data[$this->trackFieldId]) && !empty($data[$this->trackFieldId])) {
            $trackId = (int) $data[$this->trackFieldId];
        } else {
            $trackId = $this->singleTrackId ?: -1;
        }

        $sql = $this->db->quoteInto("SELECT ggp_name, ggp_name as label FROM (
                    SELECT DISTINCT ggp_name
                        FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                            INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                            INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                        WHERE ggp_group_active = 1 AND
                            gro_active=1 AND
                            gtr_active=1 AND
                            gtr_id_track = ?

                UNION ALL

                    SELECT DISTINCT gtf_field_name as ggp_name
                        FROM gems__track_fields
                        WHERE gtf_field_type = 'relation' AND
                            gtf_id_track = ?
                ) AS tmpTable
                ORDER BY ggp_name", $trackId);

        $elements[$elementId] = $this->_createSelectElement($elementId, $sql, $this->_('(all fillers)'));
    }

    /**
     * Add organization select to the elements array
     * 
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addOrgSelect(array &$elements, $data, $elementId = 'gto_id_organzation')
    {
        $orgs = $this->currentUser->getRespondentOrganizations();

        if (count($orgs) > 1) {
            if ($this->orgIsMultiCheckbox) {
                $elements[$elementId] = $this->_createMultiCheckBoxElements($elementId, $orgs, ' ');
            } else {
                $elements[$elementId] = $this->_createSelectElement($elementId, $orgs, $this->_('(all organizations)'));
            }
        }
    }

    /**
     * Add period select to the elements array
     * 
     * @param array $elements
     * @param array $data
     */
    protected function addPeriodSelect(array &$elements, $data)
    {
        $dates = array(
            'gr2t_start_date' => $this->_('Track start'),
            'gr2t_end_date'   => $this->_('Track end'),
            'gto_valid_from'  => $this->_('Valid from'),
            'gto_valid_until' => $this->_('Valid until'),
        );
        // $dates = 'gto_valid_from';
        $this->_addPeriodSelectors($elements, $dates, 'gto_valid_from');
    }

    /**
     * Add track select to the elements array
     * 
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addTrackSelect(array &$elements, $data, $elementId = 'gto_id_track')
    {
        // Store for use in addFillerSelect
        $this->trackFieldId = $elementId;
        
        $orgs   = $this->currentUser->getRespondentOrganizations();
        $tracks = $this->util->getTrackData()->getTracksForOrgs($orgs);

        if (count($tracks) > 1) {
            $elements[$elementId] = $this->_createSelectElement($elementId, $tracks, $this->_('(select a track)'));
            $elements[$elementId]->setAttrib('onchange', 'this.form.submit();');
        } else {
            $this->singleTrackId      = key($tracks);
            $elements[$elementId] = $this->form->addElement('Hidden', $elementId, ['value' => $this->singleTrackId]);
        }
    }

}
