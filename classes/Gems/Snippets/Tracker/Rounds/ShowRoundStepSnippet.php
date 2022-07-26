<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Rounds;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowRoundStepSnippet extends \Gems\Tracker\Snippets\ShowRoundSnippetAbstract
{
    /**
     *
     * @var array
     */
    private $_roundData;

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_SINGLE_ROW;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var boolean True when only tracked fields should be retrieved by the nodel
     */
    protected $trackUsage = false;

    private function _addIf(array $names, \MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        foreach ($names as $name) {
            if ($model->has($name, 'label')) {
                $bridge->addItem($name);
            }
        }
    }

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $this->_roundData = $bridge->getRow();

        if ($this->trackEngine instanceof \Gems\Tracker\Engine\StepEngineAbstract) {
            $this->trackEngine->updateRoundModelToItem($model, $this->_roundData, $this->locale->getLanguage());
        }

        $bridge->addItem('gro_id_track');
        $bridge->addItem('gro_id_survey');
        $bridge->addItem('gro_round_description');
        $bridge->addItem('gro_id_order');
        $bridge->addItem('gro_icon_file');
        
        if ($model->has('ggp_name')) {
            $bridge->addItem('ggp_name');
        } elseif ($model->has('gro_id_relationfield')) {
            $bridge->addItem('gro_id_relationfield');
        }

        $bridge->addItem($model->get('valid_after', 'value'));
        $this->_addIf(array('gro_valid_after_source', 'gro_valid_after_id', 'gro_valid_after_field'), $bridge, $model);
        if ($model->has('gro_valid_after_length', 'label')) {
            $bridge->addItem(array($bridge->gro_valid_after_length, ' ', $bridge->gro_valid_after_unit), $model->get('gro_valid_after_length', 'label'));
        }
        
        $bridge->addItem($model->get('valid_for', 'value'));
        $this->_addIf(array('gro_valid_for_source', 'gro_valid_for_id', 'gro_valid_for_field'), $bridge, $model);
        if ($model->has('gro_valid_for_length', 'label')) {
            $bridge->addItem(array($bridge->gro_valid_for_length, ' ', $bridge->gro_valid_for_unit), $model->get('gro_valid_after_length', 'label'));
        }

        $bridge->addItem('valid_cond');
        $bridge->addItem('condition_display');

        $bridge->addItem('gro_active');
        // Prevent empty row when no changed events exist
        if ($label = $model->get('gro_changed_event', 'label')) {
            $bridge->addItem('gro_changed_event');
        }
        $bridge->addItem('gro_code');
        $bridge->addItem('org_specific_round');
        if ($this->_roundData['org_specific_round']) {
            $bridge->addItem('organizations');
        }

        $menuItem = $this->menu->find(array(
            $this->request->getControllerKey() => $this->request->getControllerName(),
            $this->request->getActionKey() => 'edit'));
        if ($menuItem) {
            $bridge->tbody()->onclick = array('location.href=\'', $menuItem->toHRefAttribute($this->request), '\';');
        }
    }

    /**
     * Function that allows for overruling the repeater loading.
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @return \MUtil\Lazy\RepeatableInterface
     */
    public function getRepeater(\MUtil\Model\ModelAbstract $model)
    {
        return new \MUtil\Lazy\Repeatable(array($this->_roundData));
    }
}