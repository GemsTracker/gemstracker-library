<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds third block to fifth group group of rows showing completion data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addCompletionBlock(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
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
        $controller = $this->request->getControllerName();
        $fields[]   = $links->getActionLinks(true, $controller, 'correct', $controller, 'delete');

        $bridge->addWithThird($fields);

        return true;
    }

    /**
     * Adds second block to fifth group group of rows showing contact data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addContactBlock(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
        // E-MAIL
        $button = $links->getActionLink($this->request->getControllerName(), 'email', true);
        $bridge->addWithThird('gto_mail_sent_date', 'gto_mail_sent_num', $button);

        return true;
    }

    /**
     * Adds first group of rows showing token specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addHeaderGroup(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
        $controller = $this->request->getControllerName();
        $bridge->addItem('gto_id_token', null, array('colspan' => 1.5));
        $copiedFrom = $this->token->getCopiedFrom();
        if ($copiedFrom) {
            $bridge->tr();
            $bridge->tdh($this->_('Token copied from'));
            $bridge->td(array('colspan' => 2, 'skiprowclass' => true))
                    ->a([\MUtil_Model::REQUEST_ID => $copiedFrom], $copiedFrom);
        }
        $copiedTo = $this->token->getCopiedTo();
        if ($copiedTo) {
            $bridge->tr();
            $bridge->tdh($this->_('Token copied to'));
            $td = $bridge->td(array('colspan' => 2, 'skiprowclass' => true));
            foreach ($copiedTo as $copy) {
                $td->a([\MUtil_Model::REQUEST_ID => $copy], $copy);
                $td->append(' ');
            }
        }

        // Token status
        $bridge->tr();
        $bridge->tdh($this->_('Status'));
        $td = $bridge->td(
                ['colspan' => 2, 'skiprowclass' => true],
                $this->util->getTokenData()->getTokenStatusShowForBridge($bridge),
                ' ',
                $this->util->getTokenData()->getTokenStatusDescriptionForBridge($bridge)
                );

        // Buttons
        $bridge->gto_in_source;
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

        return true;
    }

    /**
     * Adds last group of rows cleaning up whatever is left to do in adding links etc.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addLastitems(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
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

        return true;
    }

    /**
     * Adds second group of rows showing patient specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addRespondentGroup(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
        $bridge->add('gr2o_patient_nr');
        if (! $this->currentUser->isFieldMaskedWhole('respondent_name')) {
            $bridge->add('respondent_name');
        }

        return true;
    }

    /**
     * Adds forth group of rows showing round specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addRoundGroup(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
        $bridge->add('gsu_survey_name');
        $bridge->add('gto_round_description');
        $bridge->add('ggp_name');

        return true;
    }

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

        // Extra items needed for menu items
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->grc_success;

        $links = $this->getMenuList();
        $links->addParameterSources($this->request, $bridge);

        if ($this->addHeaderGroup($bridge, $model, $links)) {
            $bridge->addMarkerRow();
        }
        if ($this->addRespondentGroup($bridge, $model, $links)) {
            $bridge->addMarkerRow();
        }
        if ($this->addTrackGroup($bridge, $model, $links)) {
            $bridge->addMarkerRow();
        }
        if ($this->addRoundGroup($bridge, $model, $links)) {
            $bridge->addMarkerRow();
        }

        $this->addValidFromBlock($bridge, $model, $links);
        $this->addContactBlock($bridge, $model, $links);
        $this->addCompletionBlock($bridge, $model, $links);

        $this->addLastitems($bridge, $model, $links);
    }

    /**
     * Adds third group of rows showing track specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addTrackGroup(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
        $bridge->add('gtr_track_name');
        $bridge->add('gr2t_track_info');
        $bridge->add('assigned_by');

        return true;
    }

    /**
     * Adds first block to fifth group group of rows showing valid from data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Gems_Menu_MenuList $links
     * @return boolean True when there was row output
     */
    protected function addValidFromBlock(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model, \Gems_Menu_MenuList $links)
    {
        // Editable part (INFO / VALID FROM / UNTIL / E-MAIL
        $button = $links->getActionLink($this->request->getControllerName(), 'edit', true);
        $bridge->addWithThird(
                'gto_valid_from_manual',
                'gto_valid_from',
                'gto_valid_until_manual',
                'gto_valid_until',
                'gto_comment',
                $button
                );

        return true;
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

        // \MUtil_Echo::track(array_keys($links->getArrayCopy()));

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
