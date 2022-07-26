<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Bridge;

/**
 * Special vertical table bridge with an extra third column,
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class ThreeColumnTableBridge extends \MUtil\Model\Bridge\VerticalTableBridge
{
    public function addMarkerRow()
    {
        $this->table->tr()->td(array('colspan' => 3));
    }

    public function add($items, $colspan = 1.5)
    {
        // 1.5 because only three total and some math later on

        foreach ((array) $items as $item) {
            $this->addItem($item, null, array('colspan' => $colspan));
        }
    }

    public function addWithThird($items_array)
    {
        $items = func_get_args();

        if ((count($items) == 1) && is_array($items[0])) {
            $items = $items[0];
        }

        if ($with = array_pop($items)) {
            $colspan = 1;
            $rowspan = count($items);
            $first   = array_shift($items);

            $this->addItem($first, null);
            $this->td($with, array('rowspan' => $rowspan));
        } else {
            $colspan = 1.5;
        }

        $this->add($items, $colspan);
    }
}
