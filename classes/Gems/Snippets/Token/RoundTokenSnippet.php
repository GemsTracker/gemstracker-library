<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Util\Translated;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class RoundTokenSnippet extends RespondentTokenSnippet
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC,
        );

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Translated
     */
    protected $translatedUtil;

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
        // \MUtil\Model::$verbose = true;
        //
        // Initiate data retrieval for stuff needed by links
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->gr2t_id_respondent_track;

        $HTML = \MUtil\Html::create();

        $roundIcon[] = \MUtil\Lazy::iif($bridge->gto_icon_file, \MUtil\Html::create('img', array('src' => $bridge->gto_icon_file, 'class' => 'icon')),
                \MUtil\Lazy::iif($bridge->gro_icon_file, \MUtil\Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon'))));

        $bridge->addMultiSort('gsu_survey_name', $roundIcon);
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, $HTML->if($bridge->is_completed, 'disabled date', 'enabled date'));
        $bridge->addSortable('gto_changed');
        $bridge->addSortable('assigned_by', $this->_('Assigned by'));

        // If we are allowed to see the result of the survey, show them
        if ($this->currentUser->hasPrivilege('pr.respondent.result') &&
                (! $this->maskRepository->isFieldMaskedWhole('gto_result'))) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }

        $bridge->useRowHref = false;

        $actionLinks[] = $this->createMenuLink($bridge, 'track',  'answer');
//      TODO
//        $actionLinks[] = array(
//            $bridge->ggp_staff_members->if(
//                    $this->createMenuLink($bridge, 'ask', 'take'),
//                    $bridge->calc_id_token->strtoupper()
//                    ),
//            'class' => $bridge->ggp_staff_members->if(null, $bridge->calc_id_token->if('token')));
        // calc_id_token is empty when the survey has been completed

        // Remove nulls
        $actionLinks = array_filter($actionLinks);
        if ($actionLinks) {
            $bridge->addItemLink($actionLinks);
        }

        $this->addTokenLinks($bridge);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        $model = parent::createModel();

        $model->set('calc_used_date',
                'formatFunction', $this->translatedUtil->formatDateNever,
                'tdClass', 'date');
        $model->set('gto_changed',
                'dateFormat', 'dd-MM-yyyy HH:mm:ss',
                'tdClass', 'date');

        return $model;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if ($this->menu) {
            if (isset($this->config['survey']['defaultTrackId'])) {
                $default = $this->config['survey']['defaultTrackId'];
                if ($this->respondent->getReceptionCode()->isSuccess()) {
                    $track = $this->loader->getTracker()->getTrackEngine($default);

                    if ($track->isUserCreatable()) {
                        $list = $this->menu->getMenuList()
                                ->addByController('track', 'create',
                                        sprintf($this->_('Add %s track to this respondent'), $track->getTrackName())
                                        )
                                ->addParameterSources(
                                        array(
                                            \Gems\Model::TRACK_ID  => $default,
                                            'gtr_id_track'         => $default,
                                            'track_can_be_created' => 1,
                                            ),
                                        $this->request
                                        );
                        $this->onEmpty = $list->getActionLink('track', 'create');
                    }
                }
            }
            if (! $this->onEmpty) {
                if ($this->respondent->getReceptionCode()->isSuccess()) {
                    $list = $this->menu->getMenuList()
                            ->addByController('track', 'show-track', $this->_('Add a track to this respondent'))
                            ->addParameterSources($this->request);
                    $this->onEmpty = $list->getActionLink('track', 'show-track');
                } else {
                    $this->onEmpty = \MUtil\Html::create('em', $this->_('No valid tokens found'));
                }
            }
        }

        return parent::hasHtmlOutput();
    }
}
