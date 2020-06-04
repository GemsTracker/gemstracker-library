<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 03-Jun-2020 16:07:15
 */
class XorModelDependency extends XandModelDependency
{
    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass()
    {
        return 'XorAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('NOT ANY (XOR) combination filter');
    }

    /**
     * Get the translated glue for the calculated name
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->_(' OR ');
    }
}
