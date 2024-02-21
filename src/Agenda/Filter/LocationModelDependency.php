<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\FilterModelDependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 18:52:40
 */
class LocationModelDependency extends FilterModelDependencyAbstract
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
    public function calculateName(mixed $value, bool $isNew = false, string|null $name = null, array $context = []): string
    {
        if (isset($context['gaf_filter_text1'])) {
            return sprintf($this->_('Location contains %s'), $context['gaf_filter_text1']);
        } else {
            return $this->_('empty filter');
        }
    }

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass(): string
    {
        return 'LocationAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName(): string
    {
        return $this->_('Location match');
    }

    /**
     * Get the settings for the gaf_filter_textN fields
     *
     * Fields not in this array are not shown in any way
     *
     * @return array gaf_filter_textN => array(modelFieldName => fieldValue)
     */
    public function getTextSettings(): array
    {
        $description = sprintf($this->_(
                "Use the %%-sign to search for zero or more random characters and an _ for a single random character."
                ));

        return array(
            'gaf_filter_text1' => array(
                'label'       => $this->_('Appointment location'),
                'description' => $description,
                'required'    => true,
                ),
            );
    }
}