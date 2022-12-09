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
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\TableElement;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

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

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        Tracker $tracker,
        protected TokenRepository $tokenDataRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $tracker);
    }

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
        // Add link to patient to overview
        $href = $this->menuHelper->getRelatedRoute('respondent.show');

        if ($href) {
            $aElem = new \Zalt\Html\AElement($href);
            $aElem->setOnEmpty('');

            // Make sure org is known
            $model->get('gr2o_id_organization');

            $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
            $model->set('respondent_name', 'itemDisplay', $aElem);
        }

        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

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

        $this->addRespondentCell($bridge, $model);
        $bridge->addMultiSort('ggp_name', [$this->createActionButtons($bridge)]);

        $tr2 = $bridge->tr();
        $tr2->appendAttrib('class', $bridge->row_class);
        $tr2->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));

        $model->set('gr2t_track_info', 'tableDisplay', [Html::class, 'smallData']);
        $model->set('gto_round_description', 'tableDisplay', [Html::class, 'smallData']);
        $bridge->addMultiSort(
            'gtr_track_name', 'gr2t_track_info',
            array($bridge->gtr_track_name->if(\MUtil\Html::raw(' &raquo; ')), ' '),
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
        $bridge->addMultiSort('gr2o_patient_nr', \MUtil\Html::raw('; '), 'respondent_name');
    }

    /**
     * Return a list of possible action buttons for the token
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @return array of HtmlElements
     */
    public function createActionButtons(TableBridge $bridge)
    {
        // Action links
        //$actionLinks['ask']    = $this->tokenDataRepository->getTokenAskLinkForBridge($bridge, true);
        //$actionLinks['email']  = $this->tokenDataRepository->getTokenEmailLinkForBridge($bridge);
        //$actionLinks['answer'] = $this->tokenDataRepository->getTokenAnswerLinkForBridge($bridge);

        $output = [];
        /*foreach ($actionLinks as $key => $actionLink) {
            if ($actionLink) {
                $output[] = ' ';
                $output[$key] = \MUtil\Html::create(
                        'div',
                        $actionLink,
                        ['class' => 'rightFloat', 'renderWithoutContent' => false, 'style' => 'clear: right;']
                        );
            }
        }*/

        return $output;
    }

    /**
     * Returns a '+' token button
     *
     * @param TableBridge $bridge
     * @return \MUtil\Html\AElement
     */
    protected function createInfoPlusCol(TableBridge $bridge)
    {
        /*return [
            'class' => 'text-right',
            $this->tokenDataRepository->getTokenStatusLinkForBridge($bridge),
            ' ',
            $this->tokenDataRepository->getTokenShowLinkForBridge($bridge, true)
            ];*/
    }

    /**
     * Returns a '+' token button
     *
     * @param TableBridge $bridge
     * @return \MUtil\Html\AElement
     */
    protected function createShowTokenButton(TableBridge $bridge)
    {
        $link = $this->tokenDataRepository->getTokenShowLinkForBridge($bridge, true);

        if ($link) {
            return $link;
        }
    }
}
