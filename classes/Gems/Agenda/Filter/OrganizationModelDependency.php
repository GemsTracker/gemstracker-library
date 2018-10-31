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
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2018, Equipe Zorgbedrijven and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 22-Oct-2018 12:19:53
 */
class OrganizationModelDependency extends FilterModelDependencyAbstract
{
    /**
     *
     * @var Gems_Util
     */
    protected $util;

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
        $options = $this->util->getDbLookup()->getOrganizations();
        $output  = [];

        foreach (['gaf_filter_text1', 'gaf_filter_text2', 'gaf_filter_text3', 'gaf_filter_text4'] as $field) {
            if (isset($context[$field], $options[$context[$field]])) {
                $output[] = $options[$context[$field]];
            }
        }

        switch (count($output)) {
            case 0:
                return $this->_('No organization');

            case 1:
                return reset($output);

            default:
                return sprintf($this->_('One of: %s'), implode($this->_(', '), $output));

        }
    }

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass()
    {
        return 'OrganizationAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('Organization filter');
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
        $options = $this->util->getTranslated()->getEmptyDropdownArray() +
                $this->util->getDbLookup()->getOrganizations();

        foreach (['gaf_filter_text1', 'gaf_filter_text2', 'gaf_filter_text3', 'gaf_filter_text4'] as $i => $field) {
            $output[$field] = [
                'label'        => $this->_('Organization') . ' ' . ($i + 1),
                'multiOptions' => $options,
                'required'     => false,
                ];
        }


        return $output;
    }
}
