<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Summary;

use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class SummaryTableSnippet extends \Gems\Snippets\ModelTableSnippet
{
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        $bridge->getTable()->setAlternateRowClass('odd', 'odd', 'even', 'even');
        $this->applyTextMarker();
        
        // \MUtil\Model::$verbose = true;

        $bridge->add(
                'gro_round_description',
                $bridge->createSortLink('gro_id_order', $model->getMetaModel()->get('gro_round_description', 'label'))
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
        $bridge->addSortable('filler');
        // $bridge->tr();
        // $bridge->add('gsu_survey_name')->colspan = 4;
        // $bridge->add('gsu_id_primary_group')->colspan = 2;
        // $bridge->addColumn();
        /*
        $bridge->addColumn(
                array(
                    $bridge->gsu_survey_name,
                    \MUtil\Html::create('em', ' - ', $bridge->gsu_id_primary_group)
                    ),
                array(
                    $model->get('gsu_survey_name', 'label'),
                    \MUtil\Html::create('em', ' - ', $model->get('gsu_id_primary_group', 'label'))
                    )
                )->colspan = 7;
        $bridge->add('removed');
         // */
    }

    /**
     *
     * @param \MUtil\Lazy\LazyInterface $part
     * @param \MUtil\Lazy\LazyInterface $total
     * @return \MUtil\Lazy\Call
     */
    public function percentageLazy($part, $total)
    {
        return Late::method($this, 'showPercentage', $part, $total);
    }

    /**
     *
     * @param int $part
     * @param int $total
     * @return string
     */
    public function showPercentage($part, $total)
    {
        if ((string) $total) {
            return sprintf($this->_('%d%%'), round(intval((string) $part) / intval((string) $total) * 100, 0));
        } else {
            return $this->_('-');
        }
    }
}