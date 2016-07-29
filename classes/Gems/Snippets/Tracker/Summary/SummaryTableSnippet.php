<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_Summary_SummaryTableSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
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
        // $bridge->getTable()->setAlternateRowClass('odd', 'odd', 'even', 'even');

        // \MUtil_Model::$verbose = true;

        $bridge->add(
                'gro_round_description',
                $bridge->createSortLink('gro_id_order', $model->get('gro_round_description', 'label'))
                );
        $bridge->addSortable('gsu_survey_name');
        $bridge->th(array($bridge->createSortLink('answered'), 'colspan' => 2))->class = 'centerAlign';
        $bridge->td($bridge->answered)->class = 'centerAlign';
        $bridge->td($this->percentageLazy($bridge->answered, $bridge->total))->class = 'rightAlign';
        $bridge->th(array($bridge->createSortLink('missed'), 'colspan' => 2))->class = 'centerAlign';
        $bridge->td($bridge->missed)->class = 'centerAlign';
        $bridge->td($this->percentageLazy($bridge->missed, $bridge->total))->class = 'rightAlign';
        $bridge->th(array($bridge->createSortLink('open'), 'colspan' => 2))->class = 'centerAlign';
        $bridge->td($bridge->open)->class = 'centerAlign';
        $bridge->td($this->percentageLazy($bridge->open, $bridge->total))->class = 'rightAlign';
        // $bridge->addSortable('answered');
        // $bridge->addSortable('missed');
        // $bridge->addSortable('open');
        // $bridge->add('future');
        // $bridge->add('unknown');
        $bridge->addColumn(array('=', 'class' => 'centerAlign'));
        $bridge->addSortable('total');
        $bridge->addSortable('ggp_name');
        // $bridge->tr();
        // $bridge->add('gsu_survey_name')->colspan = 4;
        // $bridge->add('gsu_id_primary_group')->colspan = 2;
        // $bridge->addColumn();
        /*
        $bridge->addColumn(
                array(
                    $bridge->gsu_survey_name,
                    \MUtil_Html::create('em', ' - ', $bridge->gsu_id_primary_group)
                    ),
                array(
                    $model->get('gsu_survey_name', 'label'),
                    \MUtil_Html::create('em', ' - ', $model->get('gsu_id_primary_group', 'label'))
                    )
                )->colspan = 7;
        $bridge->add('removed');
         // */
    }

    /**
     *
     * @param \MUtil_Lazy_LazyInterface $part
     * @param \MUtil_Lazy_LazyInterface $total
     * @return \MUtil_Lazy_Call
     */
    public function percentageLazy($part, $total)
    {
        return \MUtil_Lazy::method($this, 'showPercentage', $part, $total);
    }

    /**
     *
     * @param int $part
     * @param int $total
     * @return string
     */
    public function showPercentage($part, $total)
    {
        if ($total) {
            return sprintf($this->_('%d%%'), round($part / $total * 100, 0));
        } else {
            return $this->_('-');
        }
    }
}