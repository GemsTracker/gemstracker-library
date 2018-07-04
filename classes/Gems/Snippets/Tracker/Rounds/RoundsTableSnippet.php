<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RoundsTableSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
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
class RoundsTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gro_id_order' => SORT_ASC);

    /**
     * One of the \MUtil_Model_Bridge_BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_ROWS;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'track-rounds';

    /**
     *
     * @var \Gems\Tracker\Model\RoundModel
     */
    protected $model;

    /**
     * Required: the engine of the current track
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
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
        $bridge->tr()->appendAttrib('class', \MUtil_Lazy::iif(
            $bridge->gsu_active,
            '',
            'inactive'
        ));
        
        // Add link to survey-edit
        $menuItems = $this->findMenuItems('survey-maintenance', 'edit');
        if ($menuItems) {
            $menuItem = reset($menuItems);
            if ($menuItem instanceof \Gems_Menu_SubMenuItem) {
                $href = $menuItem->toHRefAttribute($bridge);

                if ($href) {
                    $aElem = new \MUtil_Html_AElement($href);
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

        $br = \MUtil_Html::create('br');
        $sp = \MUtil_Html::raw(' ');

        $this->columns[10] = array('gro_id_order');
        $this->columns[20] = array('gro_id_survey');
        $this->columns[30] = array('gro_round_description');
        $this->columns[40] = array('gro_icon_file');
        $this->columns[45] = array('ggp_name');
        $fromHeader = array(
            '', // No content
            array($this->_('Valid from'), $br)  // Force break in the header
        );
        $untilHeader = array('', array($this->_('Valid until'), $br));
        $this->columns[50] = array($fromHeader,'gro_valid_after_field', $sp, 'gro_valid_after_source', $sp, 'gro_valid_after_id');
        $this->columns[60] = array($untilHeader, 'gro_valid_for_field', $sp, 'gro_valid_for_source', $sp, 'gro_valid_for_id');
        $this->columns[70] = array('gro_active');
        if ($label = $model->get('gro_changed_event', 'label')) {
            $this->columns[80] = array('gro_changed_event');
        }
        if ($label = $model->get('gro_changed_event', 'label')) {
            $this->columns[90] = array('gro_display_event');
        }
        $this->columns[100] = array('gro_code');
        $this->columns[110] = array('condition_display');
        // Organizations can possibly be replaced with a condition
        $this->columns[120] = array('organizations');        
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface;
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof RoundModel) {
            $this->model = $this->trackEngine->getRoundModel(false, 'index');
        }

        // Now add the joins so we can sort on the real name
        // $this->model->addTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));

        // $this->model->set('gsu_survey_name', $this->model->get('gro_id_survey'));
        
        return $this->model;
    }
}
