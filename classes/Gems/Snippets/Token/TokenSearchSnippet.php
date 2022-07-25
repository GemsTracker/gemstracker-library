<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Token
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
 * @subpackage Snippets_Token
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 23-feb-2015 16:44:02
 */
class TokenSearchSnippet extends PlanSearchSnippet
{
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

        $elements[] = new \Zend_Form_Element_Hidden(\MUtil\Model::REQUEST_ID1);
        $elements[] = new \Zend_Form_Element_Hidden(\MUtil\Model::REQUEST_ID2);

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
        return $this->db->quoteInto(
                "SELECT DISTINCT gsf_id_user, CONCAT(
                            COALESCE(gems__staff.gsf_last_name, ''),
                            ', ',
                            COALESCE(gems__staff.gsf_first_name, ''),
                            COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                        ) AS gsf_name
                    FROM gems__staff INNER JOIN gems__respondent2track ON gsf_id_user = gr2t_created_by
                    WHERE $orgWhere AND
                        gr2t_id_user = ?
                    ORDER BY 2",
                $data['gto_id_respondent']
                );
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllGroups($allowedOrgs, array $data)
    {
        $orgWhere    = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";
        return $this->db->quoteInto("(SELECT DISTINCT ggp_id_group, ggp_name
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
                        gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ") AND
                        gto_id_respondent = ?
                )
                    ORDER BY ggp_name",
                $data['gto_id_respondent']
                );
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllTrackRounds($allowedOrgs, array $data)
    {
        $orgWhere    = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";

        /**
         * Explanation:
         *  Select all unique round descriptions for active rounds in active tracks
         *  Add to this the unique round descriptions for all tokens in active tracks with round id 0 (inserted round)
         */
        return $this->db->quoteInto("(SELECT DISTINCT gro_round_description, gro_round_description as gto_round_description
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
                        gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ") AND
                        gto_id_respondent = ?
                )
                ORDER BY gro_round_description",
                $data['gto_id_respondent']
                );
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllTrackTypes($allowedOrgs, array $data)
    {
        $orgWhere    = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";

        return $this->db->quoteInto(
                "SELECT gtr_id_track, gtr_track_name
                    FROM gems__tracks
                    WHERE $orgWhere AND
                        gtr_id_track IN (SELECT gr2t_id_track FROM gems__respondent2track WHERE gr2t_id_user = ?)
                    ORDER BY gtr_track_name",
                $data['gto_id_respondent']
                );
    }

    /**
     *
     * @param string $orgWhere
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllSurveys($allowedOrgs, array $data)
    {
        $orgWhere    = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";

        return $this->db->quoteInto("(SELECT DISTINCT gsu_id_survey, gsu_survey_name
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
                        gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ") AND
                        gto_id_respondent = ?
                )
                ORDER BY gsu_survey_name",
                $data['gto_id_respondent']
                );
    }

    /**
     * Return the fixed parameters
     *
     * Normally these are the hidden parameters like ID
     *
     * @return array
     */
    protected function getFixedParams()
    {
        $neededParams = parent::getFixedParams();

        $neededParams[] = \MUtil\Model::REQUEST_ID1;
        $neededParams[] = \MUtil\Model::REQUEST_ID2;

        return $neededParams;

    }

    /**
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array of organization id => name
     */
    protected function getOrganizationList(array $data)
    {
        $userOrgs = parent::getOrganizationList($data);

        if (! $data['gto_id_respondent']) {
            return $userOrgs;
        }
        $respOrgs = $this->db->fetchCol(
                "SELECT gr2o_id_organization FROM gems__respondent2org WHERE gr2o_id_user = ?",
                $data['gto_id_respondent']);
        $output = array();
        foreach ($respOrgs as $orgId) {
            if (isset($userOrgs[$orgId])) {
                $output[$orgId] = $userOrgs[$orgId];
            }
        }

        return $output;
    }
}
