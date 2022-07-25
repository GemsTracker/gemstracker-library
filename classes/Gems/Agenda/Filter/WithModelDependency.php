<?php


/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Equipe Zorgbedrijven and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\FilterModelDependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2018, Equipe Zorgbedrijven and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 22-Oct-2018 12:19:53
 */
class WithModelDependency extends FilterModelDependencyAbstract
{
    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function calcultateName($value, $isNew = false, $name = null, array $context = array())
    {
        if (isset($context['gaf_filter_text1'])) {
            return sprintf($this->_('Attended by contains %s'), $context['gaf_filter_text1']);
        } else {
            return $this->_('empty filter');
        }
    }

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass()
    {
        return 'WithAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('Attended by / with match');
    }

    /**
     * Get the settings for the gaf_filter_textN fields
     *
     * Fields not in this array are not shown in any way
     *
     * @return array gaf_filter_textN => array(modelFieldName => fieldValue)
     */
    public function getTextSettings()
    {
        $description = sprintf($this->_(
                "Use the %%-sign to search for zero or more random characters and an _ for a single random character."
                ));

        return array(
            'gaf_filter_text1' => array(
                'label'       => $this->_('Appointment is with'),
                'description' => $description,
                'required'    => true,
                ),
            );
    }
}
