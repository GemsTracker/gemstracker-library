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
class MailLogBrowseSnippet extends \Gems\Snippets\ModelTableSnippetGeneric
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
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
        $br = \MUtil\Html::create('br');
        $by = \MUtil\Html::raw($this->_(' / '));
        $sp = \MUtil\Html::raw('&nbsp;');

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
            $params = array('gto_id_token'  => $bridge->gto_id_token, \Gems\Model::ID_TYPE => 'token');
            $title  = \MUtil\Html::create('strong', $this->_('+'));


            foreach ($items as $item) {
                if ($item instanceof \Gems\Menu\SubMenuItem) {
                    $bridge->addItemLink($item->toActionLinkLower($this->request, $params, $title));
                }
            }
        }
        $bridge->getTable()->appendAttrib('class', 'compliance');

        $tbody = $bridge->tbody();
        $td = $tbody[0][0];
        $td->appendAttrib(
                'class',
                \MUtil\Lazy::method($this->util->getTokenData(), 'getStatusClass', $bridge->getLazy('status'))
                );
    }
}
