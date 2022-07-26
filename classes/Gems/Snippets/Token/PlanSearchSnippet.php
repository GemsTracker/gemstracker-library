<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Snippets\AutosearchInRespondentSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 22-apr-2015 17:15:53
 */
class PlanSearchSnippet extends AutosearchInRespondentSnippet
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var boolean
     */
    protected $multiTracks = true;

    /**
     * Display the period selector
     *
     * @var boolean
     */
    protected $periodSelector = true;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;
    
    /**
     * Add filler select to the elements array
     * 
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addFillerIdSelect(array &$elements, $data, $elementId = 'filler')
    {
        if (isset($data['gto_id_track']) && !empty($data['gto_id_track'])) {
            $trackId = (int) $data['gto_id_track'];
        } else {
            $trackId = -1;
        }
                       
        $sqlGroups = "SELECT CONCAT('g|', GROUP_CONCAT(DISTINCT ggp_id_group SEPARATOR '|')) as forgroupid, ggp_name as label
                        FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                            INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                            INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                        WHERE ggp_group_active = 1 AND
                            gro_active=1 AND
                            gtr_active=1";
        if ($trackId > -1) {
            $sqlGroups .= $this->db->quoteInto(" AND gtr_id_track = ?", $trackId);
        }
        $sqlGroups .= " GROUP BY ggp_name";
        
        $sqlRelations = "SELECT CONCAT('r|', GROUP_CONCAT(DISTINCT gtf_id_field SEPARATOR '|')) as forgroupid, gtf_field_name as label
                        FROM gems__track_fields
                        WHERE gtf_field_type = 'relation'";
        if ($trackId > -1) {
            $sqlRelations .= $this->db->quoteInto(" AND gtf_id_track = ?", $trackId);
        }
        $sqlRelations .= " GROUP BY gtf_field_name";
        
        $sql = "SELECT forgroupid, label FROM ("
                . $sqlGroups .
                " UNION ALL " .
                $sqlRelations . "
                ) AS tmpTable
                ORDER BY label";

        $elements[$elementId] = $this->_createSelectElement($elementId, $sql, $this->_('(all fillers)'));
    }

    /**
     * Add filler select to the elements array
     *
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addFillerSelect(array &$elements, $data, $elementId = 'filler')
    {
        if (isset($data['gto_id_track']) && !empty($data['gto_id_track'])) {
            $trackId = (int) $data['gto_id_track'];
        } else {
            $trackId = -1;
        }

        $sqlGroups = "SELECT DISTINCT ggp_name
                        FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                            INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                            INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                        WHERE ggp_group_active = 1 AND
                            gro_active=1 AND
                            gtr_active=1";
        if ($trackId > -1) {
            $sqlGroups .= $this->db->quoteInto(" AND gtr_id_track = ?", $trackId);
        }

        $sqlRelations = "SELECT DISTINCT gtf_field_name as ggp_name
                        FROM gems__track_fields
                        WHERE gtf_field_type = 'relation'";
        if ($trackId > -1) {
            $sqlRelations .= $this->db->quoteInto(" AND gtf_id_track = ?", $trackId);
        }

        $sql = "SELECT ggp_name, ggp_name as label FROM ("
            . $sqlGroups .
            " UNION ALL " .
            $sqlRelations . "
                ) AS tmpTable
                ORDER BY ggp_name";

        $elements[$elementId] = $this->_createSelectElement($elementId, $sql, $this->_('(all fillers)'));
    }

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
        $elements = parent::getAutoSearchElements($data);

        if ($elements) {
            $elements[] = null; // break into separate spans
        }

        if ($this->periodSelector) {
            $dates = array(
                '_gto_valid_from gto_valid_until'
                                      => $this->_('Is valid during'),
                '-gto_valid_from gto_valid_until'
                                      => $this->_('Is valid within'),
                'gto_valid_from'      => $this->_('Valid from'),
                'gto_valid_until'     => $this->_('Valid until'),
                'gto_mail_sent_date'  => $this->_('E-Mailed on'),
                'gto_completion_time' => $this->_('Completion date'),
                );
            $this->_addPeriodSelectors($elements, $dates);

            $elements[] = null; // break into separate spans
        }

        $allowedOrgs = $this->getOrganizationList($data);

        $elements['select_title'] = $this->_('Select:');
        $elements['break1']       = \MUtil\Html::create('br');

        // Select organization
        if (count($allowedOrgs) > 1) {
            $elements['gto_id_organization'] = $this->_createSelectElement(
                    'gto_id_organization',
                    $allowedOrgs,
                    $this->_('(all organizations)')
                    );
        }

        // Add track selection
        if ($this->multiTracks) {
            $elements['gto_id_track'] = $this->_createSelectElement(
                    'gto_id_track',
                    $this->getAllTrackTypes($allowedOrgs, $data),
                    $this->_('(all tracks)')
                    );
        }

        $elements['gto_round_description'] = $this->_createSelectElement(
                'gto_round_description',
                $this->getAllTrackRounds($allowedOrgs, $data),
                $this->_('(all rounds)')
                );

        $elements['gto_id_survey'] = $this->_createSelectElement(
                'gto_id_survey',
                $this->getAllSurveys($allowedOrgs, $data),
                $this->_('(all surveys)')
                );

        $elements['break2'] = \MUtil\Html::create('br');

        // Add status selection
        $elements['token_status'] = $this->_createSelectElement(
                'token_status',
                $this->getEveryStatus(),
                $this->_('(every status)')
                );

        $elements['main_filter'] = $this->_createSelectElement(
                'main_filter',
                $this->getEveryOption(),
                $this->_('(all actions)')
                );

        /*$elements['gsu_id_primary_group'] = $this->_createSelectElement(
                'gsu_id_primary_group',
                $this->getAllGroups($allowedOrgs, $data),
                $this->_('(all fillers)')
                );*/
        
        $this->addFillerIdSelect($elements, $data, 'forgroupid');

        $elements['gr2t_created_by'] = $this->_createSelectElement(
                'gr2t_created_by',
                $this->getAllCreators($allowedOrgs, $data),
                $this->_('(all staff)')
                );

        return $elements;
    }

    /**
     *
     * @param array $allowedOrgs
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllCreators(array $allowedOrgs, array $data)
    {
        if (count($allowedOrgs) > 1) {
            $orgWhere = "gr2t_id_organization IN (" . implode(", ", array_keys($allowedOrgs)) . ")";
        } else {
            reset($allowedOrgs);
            $orgWhere = "gr2t_id_organization = " . intval(key($allowedOrgs));
        }
        return "SELECT DISTINCT gsf_id_user, CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    ) AS gsf_name
                FROM gems__staff INNER JOIN gems__respondent2track ON gsf_id_user = gr2t_created_by
                WHERE $orgWhere AND
                    gr2t_active = 1
                ORDER BY 2";
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be useful, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllGroups($allowedOrgs, array $data)
    {
        if ($allowedOrgs) {
            $orgIn = "gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ")";
            $orgWhere = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";
        } else {
            $orgIn = $orgWhere = "1 = 1";
        }

        return "(SELECT DISTINCT ggp_id_group, ggp_name
                    FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                        INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE ggp_group_active = 1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        $orgWhere)

                UNION DISTINCT

                (SELECT DISTINCT ggp_id_group, ggp_name
                    FROM gems__tokens
                    INNER JOIN gems__surveys ON (gto_id_survey = gsu_id_survey AND gsu_active = 1)
                    INNER JOIN gems__groups ON (gsu_id_primary_group = ggp_id_group AND ggp_group_active = 1)
                    INNER JOIN gems__tracks ON (gto_id_track = gtr_id_track AND gtr_active = 1)
                    WHERE
                        gto_id_round = 0 AND
                        $orgIn
                )
                ORDER BY ggp_name";
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllTrackRounds($allowedOrgs, array $data)
    {
        if ($allowedOrgs) {
            $orgIn = "gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ")";
            $orgWhere = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";
        } else {
            $orgIn = $orgWhere = "1 = 1";
        }

        /**
         * Explanation:
         *  Select all unique round descriptions for active rounds in active tracks
         *  Add to this the unique round descriptions for all tokens in active tracks with round id 0 (inserted round)
         */
        return "(SELECT DISTINCT gro_round_description, gro_round_description as gto_round_description
                    FROM gems__rounds
                    INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gro_active=1 AND
                        LENGTH(gro_round_description) > 0 AND
                        gtr_active=1 AND
                        $orgWhere)
                UNION DISTINCT

                (SELECT DISTINCT gto_round_description as gro_round_description, gto_round_description
                    FROM gems__tokens
                    INNER JOIN gems__tracks ON (gto_id_track = gtr_id_track AND gtr_active = 1)
                    WHERE
                        gto_id_round = 0 AND
                        LENGTH(gto_round_description) > 0 AND
                        $orgIn
                )
                ORDER BY gro_round_description";
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllTrackTypes($allowedOrgs, array $data)
    {
        return $this->util->getTrackData()->getActiveTracks(array_keys($allowedOrgs));
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllSurveys($allowedOrgs, array $data)
    {
        if ($allowedOrgs) {
            $orgIn = "gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ")";
            $orgWhere = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";
        } else {
            $orgIn = $orgWhere = "1 = 1";
        }

        return "(SELECT DISTINCT gsu_id_survey, gsu_survey_name
                    FROM gems__surveys INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gsu_active=1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        $orgWhere)

                UNION DISTINCT

                (SELECT DISTINCT gsu_id_survey, gsu_survey_name
                    FROM gems__tokens
                    INNER JOIN gems__surveys ON (gto_id_survey = gsu_id_survey AND gsu_active = 1)
                    INNER JOIN gems__tracks ON (gto_id_track = gtr_id_track AND gtr_active = 1)
                    WHERE
                        gto_id_round = 0 AND
                        $orgIn
                )
                ORDER BY gsu_survey_name";
    }

    /**
     * The sued options
     *
     * @return array
     */
    protected function getEveryOption()
    {
        return [
            'notmailed'     => $this->_('Not emailed'),
            'tomail'        => $this->_('To email'),
            'toremind'      => $this->_('Needs reminder'),
            'hasnomail'     => $this->_('Missing email'),
            'notmailable'   => $this->_('Not allowed to email'),
        ];
    }

    /**
     * The used status actions
     *
     * @return array
     */
    protected function getEveryStatus()
    {
        return $this->util->getTokenData()->getEveryStatus();
    }

    /**
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array of organization id => name
     */
    protected function getOrganizationList(array $data)
    {
        return $this->currentUser->getRespondentOrganizations();
    }
}