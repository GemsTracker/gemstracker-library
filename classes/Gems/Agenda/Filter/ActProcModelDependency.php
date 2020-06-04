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
 * @since      Class available since version 1.6.5 15-okt-2014 18:05:13
 */
class ActProcModelDependency extends FilterModelDependencyAbstract
{
    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function calcultateName($value, $isNew = false, $name = null, array $context = array())
    {
        $output = array();
        if (isset($context['gaf_filter_text1']) && $context['gaf_filter_text1']) {
            $output[] = sprintf($this->_('Activity "%s"'), $context['gaf_filter_text1']);
        }
        if (isset($context['gaf_filter_text2']) && $context['gaf_filter_text2']) {
            $output[] = sprintf($this->_('but activity not "%s"'), $context['gaf_filter_text2']);
        }
        if (isset($context['gaf_filter_text3']) && $context['gaf_filter_text3']) {
            $output[] = sprintf($this->_('procedure "%s"'), $context['gaf_filter_text3']);
        }
        if (isset($context['gaf_filter_text4']) && $context['gaf_filter_text4']) {
            $output[] = sprintf($this->_('but procedure not "%s"'), $context['gaf_filter_text4']);
        }

        if ($output) {
            return ucfirst(implode($this->_(', '), $output));
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
        return 'ActProcAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('Activity search filter');
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
                'label'       => $this->_('Activity'),
                'description' => $description,
                'required'    => true,
                ),
            'gaf_filter_text2' => array(
                'label'       => $this->_('But not when activity'),
                'description' => sprintf($this->_("But skip when this text is found - use %%-sign as well.")),
                ),
            'gaf_filter_text3' => array(
                'label'       => $this->_('Procedure'),
                'description' => $description,
                ),
            'gaf_filter_text4' => array(
                'label'       => $this->_('But not when procedure'),
                'description' => sprintf($this->_("But skip when this text is found - use %%-sign as well.")),
                ),
            );
    }
}
