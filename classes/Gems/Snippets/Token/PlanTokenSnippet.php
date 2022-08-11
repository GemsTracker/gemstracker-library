<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

/**
 * Snippet for showing the tokens for the applied filter over multiple respondents.
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class PlanTokenSnippet extends \Gems\Snippets\TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'calc_used_date'  => SORT_ASC,
        'gtr_track_name'  => SORT_ASC,
        'gto_round_order' => SORT_ASC,
        'gto_created'     => SORT_ASC,
        );

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = true;

    /**
     *
     * @var boolean
     */
    protected $multiTracks = true;

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
        $br    = \MUtil\Html::create('br');

        // Add link to patient to overview
        $menuItems = $this->findUrls('respondent', 'show');
        if ($menuItems) {
            $menuItem = reset($menuItems);
            if ($menuItem instanceof \Gems\Menu\SubMenuItem) {
                $href = $menuItem->toHRefAttribute($bridge);

                if ($href) {
                    $aElem = new \MUtil\Html\AElement($href);
                    $aElem->setOnEmpty('');

                    // Make sure org is known
                    $model->get('gr2o_id_organization');

                    $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
                    $model->set('respondent_name', 'itemDisplay', $aElem);
                }
            }
        }

        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

        $bridge->setDefaultRowClass(\MUtil\Html\TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $tr1 = $bridge->tr();
        $tr1->appendAttrib('class', $bridge->row_class);
        $tr1->appendAttrib('title', $bridge->gto_comment);

        $bridge->addColumn(
                $this->createInfoPlusCol($bridge),
                ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addSortable('gto_id_token');
        // $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'))->rowspan = 2;

        $this->addRespondentCell($bridge, $model);
        $bridge->addMultiSort('ggp_name', [$this->createActionButtons($bridge)]);

        $tr2 = $bridge->tr();
        $tr2->appendAttrib('class', $bridge->row_class);
        $tr2->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));

        if ($this->multiTracks) {
            $model->set('gr2t_track_info', 'tableDisplay', 'smallData');
            $model->set('gto_round_description', 'tableDisplay', 'smallData');
            $bridge->addMultiSort(
                'gtr_track_name', 'gr2t_track_info',
                array($bridge->gtr_track_name->if(\MUtil\Html::raw(' &raquo; ')), ' '),
                'gsu_survey_name', 'gto_round_description');
        } else {
            $bridge->addMultiSort('gto_round_description', \MUtil\Html::raw('; '), 'gsu_survey_name');
        }
        $bridge->addSortable('assigned_by');
    }

    /**
     * As this is a common cell setting, this function allows you to overrule it.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addRespondentCell(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $bridge->addMultiSort('gr2o_patient_nr', \MUtil\Html::raw('; '), 'respondent_name');
    }

    /**
     * Return a list of possible action buttons for the token
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @return array of HtmlElements
     */
    public function createActionButtons(\MUtil\Model\Bridge\TableBridge $bridge)
    {
        $tData = $this->util->getTokenData();

        // Action links
        $actionLinks['ask']    = $tData->getTokenAskLinkForBridge($bridge, true);
        $actionLinks['email']  = $tData->getTokenEmailLinkForBridge($bridge);
        $actionLinks['answer'] = $tData->getTokenAnswerLinkForBridge($bridge);

        $output = [];
        foreach ($actionLinks as $key => $actionLink) {
            if ($actionLink) {
                $output[] = ' ';
                $output[$key] = \MUtil\Html::create(
                        'div',
                        $actionLink,
                        ['class' => 'rightFloat', 'renderWithoutContent' => false, 'style' => 'clear: right;']
                        );
            }
        }

        return $output;
    }

    /**
     * Returns a '+' token button
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @return \MUtil\Html\AElement
     */
    protected function createInfoPlusCol(\MUtil\Model\Bridge\TableBridge $bridge)
    {
        $tData = $this->util->getTokenData();

        return [
            'class' => 'text-right',
            $tData->getTokenStatusLinkForBridge($bridge),
            ' ',
            $tData->getTokenShowLinkForBridge($bridge, true)
            ];
    }

    /**
     * Returns a '+' token button
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @return \MUtil\Html\AElement
     */
    protected function createShowTokenButton(\MUtil\Model\Bridge\TableBridge $bridge)
    {
        $link = $this->util->getTokenData()->getTokenShowLinkForBridge($bridge, true);

        if ($link) {
            return $link;
        }
    }
}
