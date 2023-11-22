<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Html;
use Zalt\Html\AElement;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

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

    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();

        // Make sure org is known
        $metaModel->get('gr2o_id_organization');

        $respondentRoute = $this->menuHelper->getRelatedRoute('respondent.show');
        if ($respondentRoute) {
            $menu = $this->menuHelper->getLateRouteUrl($respondentRoute, [MetaModelInterface::REQUEST_ID1 => 'gr2o_patient_nr', MetaModelInterface::REQUEST_ID2 => 'gr2o_id_organization'], $bridge);
            $aElem = new AElement($menu['url']);
            $aElem->setOnEmpty('');


//            $metaModel->set('gr2o_patient_nr', 'itemDisplay', $aElem);
//            $metaModel->set('respondent_name', 'itemDisplay', $aElem);
        }

        $bridge->gr2t_id_respondent_track; // Data needed for edit button
        $bridge->gr2o_id_organization; // Data needed for edit button

        // Get the buttons
        $respondentRoute = $this->menuHelper->getRelatedRoute('respondent.show');
        if ($respondentRoute) {
            $menu = $this->menuHelper->getLateRouteUrl($respondentRoute, [MetaModelInterface::REQUEST_ID1 => 'gr2o_patient_nr', MetaModelInterface::REQUEST_ID2 => 'gr2o_id_organization'], $bridge);
            $respondentButton = new AElement($menu['url'], $this->_('Show respondent'), ['class' => 'actionlink btn rightFloat']);
            $respondentButton->setOnEmpty('');
        } else {
            $respondentButton = null;
        }
        $respondentTrackRoute = $this->menuHelper->getRelatedRoute('respondent.tracks.show-track');
        if ($respondentTrackRoute) {
            $menu = $this->menuHelper->getLateRouteUrl($respondentTrackRoute, [MetaModelInterface::REQUEST_ID1 => 'gr2o_patient_nr', MetaModelInterface::REQUEST_ID2 => 'gr2o_id_organization', 'rt' => 'gr2t_id_respondent_track'], $bridge);
            $trackButton = new AElement($menu['url'], $this->_('Show track'), ['class' => 'actionlink btn rightFloat']);
            $trackButton->setOnEmpty('');
        } else {
            $trackButton = null;
        }

        // Row with dates and patient data
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $bridge->addSortable('gr2o_patient_nr');
        $bridge->addSortable('respondent_name')->colspan = 2;

        $bridge->addSortable('grs_birthday');
        $bridge->addMultiSort('grs_city', array($respondentButton));

        $metaModel->set('gr2t_track_info', 'tableDisplay', [Html::class, 'smallData']);

        // Row with track info
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $td = $bridge->addMultiSort('gtr_track_name', 'gr2t_track_info');
        $td->class   = 'indentLeft';
        $td->colspan = 4;
        $td->renderWithoutContent = false; // Do not display this cell and thus this row if there is not content
        $td = $bridge->addMultiSort('track_progress', array($trackButton));
        $td->renderWithoutContent = false; // Do not display this cell and thus this row if there is not content

        $bridge->tr(array('class' => array('odd', $bridge->row_class), 'title' => $bridge->gto_comment));
        $col = $bridge->addColumn(
                $this->createInfoPlusCol($bridge),
                ' '); // Space needed because TableElement does not look at rowspans
        $col->rowspan = 2;

        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');
        $metaModel->set('gto_round_description', 'tableDisplay', [Html::class, 'smallData']);
        $bridge->addMultiSort('gsu_survey_name', 'gto_round_description')->colspan = 2;

        $bridge->tr(array('class' => array('odd', $bridge->row_class), 'title' => $bridge->gto_comment));
        // $bridge->addColumn();
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_id_token');
        $bridge->addMultiSort('ggp_name', [$this->createActionButtons($bridge)]);
    }
}
