<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Rounds;

use Gems\Html;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Tracker\Model\RoundModel;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 21-apr-2015 13:39:42
 */
class RoundsTableSnippet extends ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['gro_id_order' => SORT_ASC];

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = BridgeAbstract::MODE_ROWS;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'track-rounds';

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditRoutes = ['track-builder.track-maintenance.track-rounds.edit'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowRoutes = ['track-builder.track-maintenance.track-rounds.show'];

    /**
     *
     * @var \Gems\Tracker\Model\RoundModel
     */
    protected $model;

    /**
     * Required: the engine of the current track
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();

        // Make sure these fields are loaded
        $metaModel->get('gro_valid_after_field');
        $metaModel->get('gro_valid_after_id');
        $metaModel->get('gro_valid_after_length');
        $metaModel->get('gro_valid_after_source');
        $metaModel->get('gro_valid_after_unit');

        $metaModel->get('gro_valid_for_field');
        $metaModel->get('gro_valid_for_id');
        $metaModel->get('gro_valid_for_length');
        $metaModel->get('gro_valid_for_source');
        $metaModel->get('gro_valid_for_unit');

        // We want to markt the row for inactive surveys so it visually stands out
        $metaModel->get('gsu_active');
//        $bridge->tr()->appendAttrib('class', \MUtil\Lazy::iif(
//            $bridge->gsu_active,
//            '',
//            'inactive'
//        ));

        // Add link to survey-edit
//        foreach ($this->getEditUrls($bridge, $dataModel->getKeys()) as $linkParts) {
//            if (! isset($linkParts['label'])) {
//                $linkParts['label'] = $this->_('Edit');
//            }
//            $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
//        }

        parent::addBrowseTableColumns($bridge, $dataModel);
    }

//    public function getRequestFilter(MetaModelInterface $metaModel) : array
//    {
//    }

    protected function cleanUpFilter(array $filter, MetaModelInterface $metaModel): array
    {
        if (isset($filter['trackId'])) {
            $filter['gro_id_track'] = $filter['trackId'];
            unset($filter['trackId']);
        } elseif ($this->requestInfo->getParam('trackId')) {
            $filter['gro_id_track'] = $this->requestInfo->getParam('trackId');
        }
        return parent::cleanUpFilter($filter, $metaModel);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof RoundModel) {
            $this->model = $this->trackEngine->getRoundModel(false, 'index');
            $this->model->getMetaModel()->setKeys(['rid' => 'gro_id_round']);
        }
        $this->extraFilter['gro_id_track'] = $this->trackEngine->getTrackId();

        $br = Html::create('br');
        $sp = Html::raw(' ');

        if (!is_array($this->columns)) {
            $this->columns = [];
        }

        $this->columns[10] = ['gro_id_order'];
        $this->columns[20] = ['gro_id_survey'];
        $this->columns[30] = ['gro_round_description'];
        $this->columns[40] = ['gro_icon_file'];
        $this->columns[45] = ['ggp_name'];
        $fromHeader = [
            '', // No content
            [$this->_('Valid from'), $br]  // Force break in the header
        ];
        $untilHeader = ['', [$this->_('Valid until'), $br]];
        $this->columns[50] = [$fromHeader,'gro_valid_after_field', $sp, 'gro_valid_after_source', $sp, 'gro_valid_after_id'];
        $this->columns[60] = [$untilHeader, 'gro_valid_for_field', $sp, 'gro_valid_for_source', $sp, 'gro_valid_for_id'];
        $this->columns[70] = ['gro_active'];
        if ($this->model->getMetaModel()->get('gro_changed_event', 'label')) {
            $this->columns[80] = ['gro_changed_event'];
        }
        if ($this->model->getMetaModel()->get('gro_changed_event', 'label')) {
            $this->columns[90] = ['gro_display_event'];
        }
        $this->columns[100] = ['gro_code'];
        $this->columns[110] = ['condition_display'];
        // Organizations can possibly be replaced with a condition
        $this->columns[120] = ['organizations'];

        return $this->model;
    }

    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        $output = parent::getRouteMaps($metaModel);
        $output['trackId'] = 'gro_id_track';
        return $output;
    }
}
