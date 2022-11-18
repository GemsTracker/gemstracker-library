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

use Gems\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        $br    = \MUtil\Html::create('br');
        $tData = $this->util->getTokenData();

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

        $model->set('gto_round_description', 'tableDisplay', [Html::class, 'smallData']);
        $bridge->addMultiSort('gsu_survey_name', 'gto_round_description');
        $bridge->addMultiSort('ggp_name', [$this->createActionButtons($bridge)]);

        $tr2 = $bridge->tr();
        $tr2->appendAttrib('class', $bridge->row_class);
        $tr2->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));

        if ($this->multiTracks) {
            $model->set('gr2t_track_info', 'tableDisplay', [Html::class, 'smallData']);
            $bridge->addMultiSort('gtr_track_name', 'gr2t_track_info');
        } else {
            $bridge->addSortable('gr2t_track_info');
        }
        $bridge->addSortable('assigned_by');
    }
}
