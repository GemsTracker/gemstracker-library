<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: PlanSearchSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
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
     * @var \Gems_User_User
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
     * @var \Gems_Util
     */
    protected $util;

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

        $elements[] = $this->_('Select:');
        $elements[] = \MUtil_Html::create('br');
        
        \MUtil_Echo::track($this->getAllSurveys($allowedOrgs, $data));
        \MUtil_Echo::track($this->getAllGroups($allowedOrgs, $data));
        \MUtil_Echo::track($this->getAllTrackRounds($allowedOrgs, $data));

        // Add track selection
        if ($this->multiTracks) {
            $elements[] = $this->_createSelectElement(
                    'gto_id_track',
                    $this->getAllTrackTypes($allowedOrgs, $data),
                    $this->_('(all tracks)')
                    );
        }

        $elements[] = $this->_createSelectElement(
                'gto_round_description',
                $this->getAllTrackRounds($allowedOrgs, $data),
                $this->_('(all rounds)')
                );

        $elements[] = $this->_createSelectElement(
                'gto_id_survey',
                $this->getAllSurveys($allowedOrgs, $data),
                $this->_('(all surveys)')
                );

        $options = array(
            'all'       => $this->_('(all actions)'),
            'open'      => $this->_('Open'),
            'notmailed' => $this->_('Not emailed'),
            'tomail'    => $this->_('To email'),
            'toremind'  => $this->_('Needs reminder'),
            'hasnomail' => $this->_('Missing email'),
            'toanswer'  => $this->_('Yet to Answer'),
            'answered'  => $this->_('Answered'),
            'missed'    => $this->_('Missed'),
            'removed'   => $this->_('Removed'),
            );
        $elements[] = $this->_createSelectElement('main_filter', $options);

        $elements[] = $this->_createSelectElement(
                'gsu_id_primary_group',
                $this->getAllGroups($allowedOrgs, $data),
                $this->_('(all fillers)')
                );

        // Select organisation
        if (count($allowedOrgs) > 1) {
            $elements[] = $this->_createSelectElement(
                    'gto_id_organization',
                    $allowedOrgs,
                    $this->_('(all organizations)')
                    );
        }

        $elements[] = $this->_createSelectElement(
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
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return mixed SQL string or array
     */
    protected function getAllGroups($allowedOrgs, array $data)
    {
        $orgWhere    = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";
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
                        gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ")
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
        $orgWhere    = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($allowedOrgs)) .
                "|') > 0)";        
        
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
                        gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ")
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
        return $this->util->getTrackData()->getActiveTracks($allowedOrgs);
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
                        gto_id_organization IN (" . implode(',', array_keys($allowedOrgs)) . ")
                )
                ORDER BY gsu_survey_name";
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