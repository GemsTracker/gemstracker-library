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
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Tracker\Model\RoundModel;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
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
    //public array $menuEditRoutes = ['track-builder.track-maintenance.track-rounds.edit'];

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
     * @param DataReaderInterface $model
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        // Make sure these fields are loaded
        $model->get('gro_valid_after_field');
        $model->get('gro_valid_after_id');
        $model->get('gro_valid_after_length');
        $model->get('gro_valid_after_source');
        $model->get('gro_valid_after_unit');

        $model->get('gro_valid_for_field');
        $model->get('gro_valid_for_id');
        $model->get('gro_valid_for_length');
        $model->get('gro_valid_for_source');
        $model->get('gro_valid_for_unit');

        // We want to markt the row for inactive surveys so it visually stands out
        $model->get('gsu_active');
//        $bridge->tr()->appendAttrib('class', \MUtil\Lazy::iif(
//            $bridge->gsu_active,
//            '',
//            'inactive'
//        ));

        // Add link to survey-edit
//        foreach ($this->getEditUrls($bridge, $model->getKeys()) as $linkParts) {
//            if (! isset($linkParts['label'])) {
//                $linkParts['label'] = $this->_('Edit');
//            }
//            $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
//        }

        parent::addBrowseTableColumns($bridge, $model);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     * /
    public function afterRegistry()
    {
        parent::afterRegistry();

        $model = $this->getModel();

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
        if ($label = $model->get('gro_changed_event', 'label')) {
            $this->columns[80] = ['gro_changed_event'];
        }
        if ($label = $model->get('gro_changed_event', 'label')) {
            $this->columns[90] = ['gro_display_event'];
        }
        $this->columns[100] = ['gro_code'];
        $this->columns[110] = ['condition_display'];
        // Organizations can possibly be replaced with a condition
        $this->columns[120] = ['organizations'];
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
            $this->model->setKeys(['rid' => 'gro_id_round']);
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
        if ($label = $this->model->get('gro_changed_event', 'label')) {
            $this->columns[80] = ['gro_changed_event'];
        }
        if ($label = $this->model->get('gro_changed_event', 'label')) {
            $this->columns[90] = ['gro_display_event'];
        }
        $this->columns[100] = ['gro_code'];
        $this->columns[110] = ['condition_display'];
        // Organizations can possibly be replaced with a condition
        $this->columns[120] = ['organizations'];

        return $this->model;
    }

}
