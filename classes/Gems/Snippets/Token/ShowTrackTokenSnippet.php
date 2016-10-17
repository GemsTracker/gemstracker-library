<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

/**
 * Display snippet for standard track tokens
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowTrackTokenSnippet extends \Gems_Tracker_Snippets_ShowTokenSnippetAbstract
{
    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // \MUtil_Model::$verbose = true;

        // Extra item needed for menu items
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->grc_success;

        $controller = $this->request->getControllerName();
        $links      = $this->getMenuList();
        $links->addParameterSources($this->request, $bridge);

        $bridge->addItem('gto_id_token', null, array('colspan' => 1.5));

        $buttons = $links->getActionLinks(true,
                'ask', 'take',
                'pdf', 'show',
                $controller, 'questions',
                $controller, 'answer',
                $controller, 'answer-export'
                );
        if (count($buttons)) {
            $bridge->tr();
            $bridge->tdh($this->_('Actions'));
            $bridge->td($buttons, array('colspan' => 2, 'skiprowclass' => true));
        }
        $bridge->addMarkerRow();

        $bridge->add('gr2o_patient_nr');
        $bridge->add('respondent_name');
        $bridge->addMarkerRow();

        $bridge->add('gtr_track_name');
        $bridge->add('gr2t_track_info');
        $bridge->add('assigned_by');
        $bridge->addMarkerRow();

        $bridge->add('gsu_survey_name');
        $bridge->add('gto_round_description');
        $bridge->add('ggp_name');
        $bridge->addMarkerRow();

        // Editable part (INFO / VALID FROM / UNTIL / E-MAIL
        $button = $links->getActionLink($controller, 'edit', true);
        $bridge->addWithThird('gto_valid_from_manual', 'gto_valid_from', 'gto_valid_until_manual', 'gto_valid_until', 'gto_comment', $button);

        // E-MAIL
        $button = $links->getActionLink($controller, 'email', true);
        $bridge->addWithThird('gto_mail_sent_date', 'gto_mail_sent_num', $button);

        // COMPLETION DATE
        $fields = array();
        if ($this->token->getReceptionCode()->hasDescription()) {
            $bridge->addMarkerRow();
            $fields[] = 'grc_description';
        }
        $fields[] = 'gto_completion_time';
        if ($this->token->isCompleted()) {
            $fields[] = 'gto_duration_in_sec';
        }
        if ($this->token->hasResult()) {
            $fields[] = 'gto_result';
        }
        $fields[] = $links->getActionLinks(true, $controller, 'correct', $controller, 'delete');

        $bridge->addWithThird($fields);

        if ($links->count()) {
            $bridge->tfrow($links, array('class' => 'centerAlign'));
        }

        foreach ($bridge->tbody() as $row) {
            if (isset($row[1]) && ($row[1] instanceof \MUtil_Html_HtmlElement)) {
                if (isset($row[1]->skiprowclass)) {
                    unset($row[1]->skiprowclass);
                } else {
                    $row[1]->appendAttrib('class', $bridge->row_class);
                }
            }
        }
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addCurrentParent($this->_('Show track'))
                ->addCurrentSiblings()
                ->addCurrentChildren()
                ->showDisabled();

        // \MUtil_Echo::track($links->count());

        return $links;
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        return $this->_('Show token');
    }
}
