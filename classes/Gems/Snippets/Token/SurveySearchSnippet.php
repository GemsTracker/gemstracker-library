<?php

/**
 * Copyright (c) 2015, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SurveySearchSnippet.php $
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
class SurveySearchSnippet extends PlanSearchSnippet
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

        $elements[] = new \Zend_Form_Element_Hidden(\MUtil_Model::REQUEST_ID1);
        $elements[] = new \Zend_Form_Element_Hidden(\MUtil_Model::REQUEST_ID2);

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
    protected function getAllGroups($orgWhere, array $data)
    {
        return $this->db->quoteInto(
                "SELECT DISTINCT ggp_id_group, ggp_name
                    FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                        INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE $orgWhere AND
                        gtr_id_track IN (SELECT gr2t_id_track FROM gems__respondent2track WHERE gr2t_id_user = ?)
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
    protected function getAllTrackRounds($orgWhere, array $data)
    {
        return $this->db->quoteInto(
                "SELECT DISTINCT gro_round_description, gro_round_description
                    FROM gems__rounds INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE LENGTH(gro_round_description) > 0 AND
                        $orgWhere AND
                        gtr_id_track IN (SELECT gr2t_id_track FROM gems__respondent2track WHERE gr2t_id_user = ?)
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
    protected function getAllTrackTypes($orgWhere, array $data)
    {
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
    protected function getAllSurveys($orgWhere, array $data)
    {
        return $this->db->quoteInto(
                "SELECT DISTINCT gsu_id_survey, gsu_survey_name
                    FROM gems__surveys INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gsu_active=1 AND
                        $orgWhere AND
                        gtr_id_track IN (SELECT gr2t_id_track FROM gems__respondent2track WHERE gr2t_id_user = ?)
                    ORDER BY gsu_survey_name",
                $data['gto_id_respondent']
                );
    }

    /**
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array of organization id => name
     */
    protected function getOrganizationList(array $data)
    {
        $userOrgs = parent::getOrganizationList($data);

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
