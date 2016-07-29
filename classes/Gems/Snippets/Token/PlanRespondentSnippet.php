<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: PlanRespondentSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Token;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 16-okt-2015 19:04:21
 */
class PlanRespondentSnippet extends PlanTokenSnippet
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'respondent_name'       => SORT_ASC,
        'gr2o_patient_nr'       => SORT_ASC,
        'gtr_track_name'        => SORT_ASC,
        'gtr_track_info'        => SORT_ASC,
        'gr2t_track_info'       => SORT_ASC,
        'gto_round_description' => SORT_ASC,
        );

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
        $bridge->gr2t_id_respondent_track; // Data needed for edit button
        $bridge->gr2o_id_organization; // Data needed for edit button

        $HTML = \MUtil_Html::create();

        // Get the buttons
        $respMenu = $this->menu->findAllowedController('respondent', 'show');
        if ($respMenu) {
            $respondentButton = $respMenu->toActionLink($this->request, $bridge, $this->_('Show respondent'));
            $respondentButton->appendAttrib('class', 'rightFloat');
        } else {
            $respondentButton = null;
        }
        $trackMenu = $this->menu->findAllowedController('respondent', 'show-track');
        if ($trackMenu) {
            $trackButton = $trackMenu->toActionLink($this->request, $bridge, $this->_('Show track'));
            $trackButton->appendAttrib('class', 'rightFloat');
        } else {
            $trackButton = null;
        }

        // Row with dates and patient data
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $bridge->addSortable('gr2o_patient_nr');
        $bridge->addSortable('respondent_name')->colspan = 2;

        if ($this->multiTracks) {
            $bridge->addSortable('grs_birthday');
            $bridge->addMultiSort('grs_city', array($respondentButton));

            $model->set('gr2t_track_info', 'tableDisplay', 'smallData');

            // Row with track info
            $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
            $td = $bridge->addMultiSort('gtr_track_name', 'gr2t_track_info');
            $td->class   = 'indentLeft';
            $td->colspan = 4;
            $td->renderWithoutContent = false; // Do not display this cell and thus this row if there is not content
            $td = $bridge->addMultiSort('progress', array($trackButton));
            $td->renderWithoutContent = false; // Do not display this cell and thus this row if there is not content
        } else {
            $bridge->addSortable('grs_birthday');
            $bridge->addMultiSort('progress', array($respondentButton));
        }

        $bridge->tr(array('class' => array('odd', $bridge->row_class), 'title' => $bridge->gto_comment));
        $bridge->addColumn($this->createShowTokenButton($bridge))->class = 'rightAlign';
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');
        $model->set('gto_round_description', 'tableDisplay', 'smallData');
        $bridge->addMultiSort('gsu_survey_name', 'gto_round_description')->colspan = 2;

        $bridge->tr(array('class' => array('odd', $bridge->row_class), 'title' => $bridge->gto_comment));
        $bridge->addColumn();
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_id_token');
        $bridge->addMultiSort('ggp_name', array($this->createActionButtons($bridge)));
    }
}
