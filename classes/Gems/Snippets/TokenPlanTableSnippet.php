<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Displays a table for TokenModel
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
  */
class Gems_Snippets_TokenPlanTableSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
    public $filter = array();

    /**
     * @var \GemsEscort
     */
    public $escort;

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
    public function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        $HTML  = \MUtil_Html::create();

        $bridge->setDefaultRowClass(\MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $bridge->addColumn($this->getTokenLinks($bridge), ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addMultiSort('gr2o_patient_nr', $HTML->raw('; '), 'respondent_name');
        $bridge->addMultiSort('ggp_name', array($this->getActionLinks($bridge)));

        $bridge->tr();
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');

        if ($this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface) {
            $bridge->addMultiSort('gto_round_description', $HTML->raw('; '), 'gsu_survey_name');
        } else {
            $model->set('gr2t_track_info', 'tableDisplay', 'smallData');
            $model->set('gto_round_description', 'tableDisplay', 'smallData');
            $bridge->addMultiSort(
                'gtr_track_name', 'gr2t_track_info',
                $bridge->gtr_track_name->if($HTML->raw(' &raquo; ')),
                'gsu_survey_name', 'gto_round_description');
        }

        $bridge->addSortable('assigned_by');
    }

    public function getActionLinks(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        // Get the other token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => 'track', 'action' => array('email', 'answer'), 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->request, $bridge);
            $buttons->appendAttrib('class', 'rightFloat');
        } else {
            $buttons = null;
        }
        // Add the ask button
        if ($menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $askLink = $menuItem->toActionLink($this->request, $bridge);
            $askLink->appendAttrib('class', 'rightFloat');

            if ($buttons) {
                // Show previous link if show, otherwise show ask link
                $buttons = array($buttons, $askLink);
            } else {
                $buttons = $askLink;
            }
        }

        return $buttons;
    }

    public function getTokenLinks(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        // Get the token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => 'track', 'action' => 'show', 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->request, $bridge, $this->_('+'));
            $buttons->title = $bridge->gto_id_token->strtoupper();

            return $buttons;
        }
    }

    public function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        if (!empty($this->filter)) {
            $model->setFilter($this->filter);
        }

        parent::processFilterAndSort($model);

        if (!empty($this->filter)) {
            $filter = $model->getFilter();
            unset($filter['gto_id_token']);
            $model->setFilter($filter);
        }
    }
}