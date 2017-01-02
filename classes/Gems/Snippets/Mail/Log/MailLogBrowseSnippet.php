<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail\Log;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class MailLogBrowseSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

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
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->getShowMenuItems();

            foreach ($showMenuItems as $menuItem) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->request, $bridge));
            }
        }

        // Newline placeholder
        $br = \MUtil_Html::create('br');
        $by = \MUtil_Html::raw($this->_(' / '));
        $sp = \MUtil_Html::raw('&nbsp;');

        // make sure search results are highlighted
        $this->applyTextMarker();

        if ($this->currentUser->areAllFieldsMaskedWhole('respondent_name', 'grs_surname_prefix', 'grco_address')) {
            $bridge->addMultiSort('grco_created',  $br, 'gr2o_patient_nr', $br, 'gtr_track_name');
        } else {
            $bridge->addMultiSort('grco_created',  $br, 'gr2o_patient_nr', $sp, 'respondent_name', $br, 'grco_address', $br, 'gtr_track_name');
        }
        $bridge->addMultiSort('grco_id_token', $br, 'assigned_by',     $br, 'grco_sender',     $br, 'gsu_survey_name');
        $bridge->addMultiSort('status',        $by, 'filler',          $br, 'grco_topic');

        if ($this->showMenu) {
            $items  = $this->findMenuItems('track', 'show');
            $links  = array();
            $params = array('gto_id_token'  => $bridge->gto_id_token, \Gems_Model::ID_TYPE => 'token');
            $title  = \MUtil_Html::create('strong', $this->_('+'));


            foreach ($items as $item) {
                if ($item instanceof \Gems_Menu_SubMenuItem) {
                    $bridge->addItemLink($item->toActionLinkLower($this->request, $params, $title));
                }
            }
        }
        $bridge->getTable()->appendAttrib('class', 'compliance');

        $tbody = $bridge->tbody();
        $td = $tbody[0][0];
        $td->appendAttrib(
                'class',
                \MUtil_Lazy::method($this->util->getTokenData(), 'getStatusClass', $bridge->getLazy('status'))
                );
    }
}
