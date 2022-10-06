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

use Gems\Tracker\Model\RoundModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 21-apr-2015 13:39:42
 */
class RoundsTableSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
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
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_ROWS;

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
    public array $menuEditActions = ['track-builder.track-maintenance.track-rounds.edit'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowActions = ['track-builder.track-maintenance.track-rounds.show'];

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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
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
        $bridge->tr()->appendAttrib('class', \MUtil\Lazy::iif(
            $bridge->gsu_active,
            '',
            'inactive'
        ));

        // Add link to survey-edit
        $menuItems = $this->findUrls('edit', $bridge);
        if ($menuItems) {
            $menuItem = reset($menuItems);
            if ($menuItem instanceof \Gems\Menu\SubMenuItem) {
                $href = $menuItem->toHRefAttribute(['id' => $bridge->getLazy('gro_id_survey')]);

                if ($href) {
                    $aElem = new \MUtil\Html\AElement($href);
                    $aElem->setOnEmpty('');

                    $model->set('gro_id_survey', 'itemDisplay', $aElem);
                }
            }
        }

        parent::addBrowseTableColumns($bridge, $model);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $model = $this->getModel();

        $br = \MUtil\Html::create('br');
        $sp = \MUtil\Html::raw(' ');

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
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface;
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof RoundModel) {
            $this->model = $this->trackEngine->getRoundModel(false, 'index');
            $this->model->applyParameters(['gro_id_track' => $this->trackEngine->getTrackId()]);
        }

        // Now add the joins so we can sort on the real name
        // $this->model->addTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));

        // $this->model->set('gsu_survey_name', $this->model->get('gro_id_survey'));

        return $this->model;
    }
}
