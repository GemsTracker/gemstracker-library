<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

/**
 * Snippet for showing the all tokens for a single respondent.
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class RespondentPlanTokenSnippet extends PlanTokenSnippet
{
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br    = \MUtil_Html::create('br');
        $tData = $this->util->getTokenData();

        // Add link to patient to overview
        $menuItems = $this->findMenuItems('respondent', 'show');
        if ($menuItems) {
            $menuItem = reset($menuItems);
            if ($menuItem instanceof \Gems_Menu_SubMenuItem) {
                $href = $menuItem->toHRefAttribute($bridge);

                if ($href) {
                    $aElem = new \MUtil_Html_AElement($href);
                    $aElem->setOnEmpty('');

                    // Make sure org is known
                    $model->get('gr2o_id_organization');

                    $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
                    $model->set('respondent_name', 'itemDisplay', $aElem);
                }
            }
        }

        $model->get('gtr_track_name');
        $model->get('gr2t_track_info');
        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

        $bridge->setDefaultRowClass(\MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $tr1 = $bridge->tr();
        $tr1->appendAttrib('class', $bridge->row_class);
        $tr1->appendAttrib('title', $bridge->gto_comment);

        $bridge->addColumn(
                [$tData->getTokenStatusLinkForBridge($bridge), $br, $tData->getTokenShowLinkForBridge($bridge, true)],
                ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addSortable('gto_id_token');
        // $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'))->rowspan = 2;

        $model->set('gto_round_description', 'tableDisplay', 'smallData');
        $bridge->addMultiSort('gsu_survey_name', 'gto_round_description');
        $bridge->addMultiSort('ggp_name', array($this->createActionButtons($bridge)));

        $tr2 = $bridge->tr();
        $tr2->appendAttrib('class', $bridge->row_class);
        $tr2->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));

        if ($this->multiTracks) {
            $model->set('gr2t_track_info', 'tableDisplay', 'smallData');
            $bridge->addMultiSort('gtr_track_name', 'gr2t_track_info');
        } else {
            $bridge->addSortable('gr2t_track_info');
        }
        $bridge->addSortable('assigned_by');
    }

}
