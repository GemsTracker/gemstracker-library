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

use Gems\Html;
use Gems\Snippets\TokenModelSnippetAbstract;
use MUtil\Model;
use MUtil\Model\ModelAbstract;
use Zalt\Html\AElement;
use Zalt\Html\TableElement;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 * Snippet for showing the tokens for the applied filter over multiple respondents.
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class PlanTokenSnippet extends TokenModelSnippetAbstract
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
     * @inheritdoc 
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        // Add link to patient to overview
        $respondentRoute = $this->menuHelper->getRelatedRoute('respondent.show');
        if ($respondentRoute) {
            $menu = $this->menuHelper->getLateRouteUrl($respondentRoute, [Model::REQUEST_ID1 => 'gr2o_patient_nr', Model::REQUEST_ID2 => 'gr2o_id_organization'], $bridge);
            $aElem = new AElement($menu['url'], ['class' => '']);
            $aElem->setOnEmpty('');

            // Make sure org is known
            $dataModel->get('gr2o_id_organization');

            $dataModel->set('gr2o_patient_nr', 'itemDisplay', $aElem);
            $dataModel->set('respondent_name', 'itemDisplay', $aElem);
        }

        $dataModel->set('gto_id_token', 'formatFunction', 'strtoupper');

        $bridge->setDefaultRowClass(TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
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

        $this->addRespondentCell($bridge, $dataModel);
        $bridge->addMultiSort('ggp_name', [$this->createActionButtons($bridge)]);

        $tr2 = $bridge->tr();
        $tr2->appendAttrib('class', $bridge->row_class);
        $tr2->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));

        $dataModel->set('gr2t_track_info', 'tableDisplay', [Html::class, 'smallData']);
        $dataModel->set('gto_round_description', 'tableDisplay', [Html::class, 'smallData']);
        $bridge->addMultiSort(
            'gtr_track_name', 'gr2t_track_info',
            array($bridge->gtr_track_name->if(Html::raw(' &raquo; ')), ' '),
            'gsu_survey_name', 'gto_round_description');

        $bridge->addSortable('assigned_by');
    }

    /**
     * As this is a common cell setting, this function allows you to overrule it.
     *
     * @param TableBridge $bridge
     * @param ModelAbstract $model
     */
    protected function addRespondentCell(TableBridge $bridge, ModelAbstract $model)
    {
        $bridge->addMultiSort('gr2o_patient_nr', Html::raw('; '), 'respondent_name');
    }

    /**
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @return AElement[] 
     */
    public function createActionButtons(TableBridge $bridge): array
    {
        // Action links
        $actionLinks['ask']    = $this->tokenRepository->getTokenAskLinkForBridge($bridge, $this->menuHelper, true);
        $actionLinks['email']  = $this->tokenRepository->getTokenEmailLinkForBridge($bridge, $this->menuHelper);
        $actionLinks['answer'] = $this->tokenRepository->getTokenAnswerLinkForBridge($bridge, $this->menuHelper);

        $output = [];
        foreach ($actionLinks as $key => $actionLink) {
            if ($actionLink) {
                $output[] = ' ';
                $output[$key] = Html::create(
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
     * @param TableBridge $bridge
     * @return 
     */
    protected function createInfoPlusCol(TableBridge $bridge)
    {
        return [
            'class' => 'text-right',
            $this->tokenRepository->getTokenStatusLinkForBridge($bridge, $this->menuHelper),
            ' ',
            $this->tokenRepository->getTokenShowLinkForBridge($bridge, $this->menuHelper, true)
            ];
    }

    /**
     * Returns a '+' token button
     *
     * @param TableBridge $bridge
     * @return ?AElement
     */
    protected function createShowTokenButton(TableBridge $bridge)
    {
        $link = $this->tokenDataRepository->getTokenShowLinkForBridge($bridge, $this->menuHelper, true);

        if ($link) {
            return $link;
        }
    }
}
