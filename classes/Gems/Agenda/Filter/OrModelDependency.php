<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OrModelDependency.php $
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
 * @since      Class available since version 1.6.5 16-okt-2014 16:56:22
 */
class OrModelDependency extends FilterModelDependencyAbstract
{
    /**
     *
     * @var array filter_id => label
     */
    protected $_filters;

    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->_filters = $this->util->getTranslated()->getEmptyDropdownArray() + $this->agenda->getFilterList();

        parent::afterRegistry();
    }

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
        // Check all the fields
        for ($i = 1; $i <= $this->_fieldCount; $i++) {
            $field = 'gaf_filter_text' . $i;
            if (isset($context[$field], $this->_filters[$context[$field]]) && $context[$field] && $this->_filters[$context[$field]]) {
                $output[] = $this->_filters[$context[$field]];
            }
        }

        if ($output) {
            return ucfirst(implode($this->_(' OR '), $output));
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
        return 'OrAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('OR combination filter');
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
        $messages = array(
            'gaf_id' => $this->_('Sub filter may not be the same as this filter.'),
            $this->_('Filters may be chosen only once.')
                );

        return array(
            'gaf_filter_text1' => array(
                'label'        => $this->_('Filter 1'),
                'elementClass' => 'Select',
                'multiOptions' => $this->_filters,
                'required'     => true,
                'validator'    => new \MUtil_Validate_NotEqualTo('gaf_id', $messages),
                ),
            'gaf_filter_text2' => array(
                'label'        => $this->_('Filter 2'),
                'elementClass' => 'Select',
                'multiOptions' => $this->_filters,
                'required'     => true,
                'validator'    => new \MUtil_Validate_NotEqualTo(array('gaf_id', 'gaf_filter_text1'), $messages),
                ),
            'gaf_filter_text3' => array(
                'label'        => $this->_('Filter 3'),
                'elementClass' => 'Select',
                'multiOptions' => $this->_filters,
                'validator'    => new \MUtil_Validate_NotEqualTo(
                        array('gaf_id', 'gaf_filter_text1', 'gaf_filter_text2'),
                        $messages
                        ),
                ),
            'gaf_filter_text4' => array(
                'label'        => $this->_('Filter 4'),
                'elementClass' => 'Select',
                'multiOptions' => $this->_filters,
                'validator'    => new \MUtil_Validate_NotEqualTo(
                        array('gaf_id', 'gaf_filter_text1', 'gaf_filter_text2', 'gaf_filter_text3'),
                        $messages
                        ),
                ),
            );
    }
}
