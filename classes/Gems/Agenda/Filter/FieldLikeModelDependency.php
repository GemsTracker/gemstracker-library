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
use Gems\Util\Translated;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 18:05:13
 */
class FieldLikeModelDependency extends FilterModelDependencyAbstract
{
    /**
     *
     * @var \Gems\Agenda
     */
    protected $agenda;

    /**
     * @var Translated
     */
    protected $translatedUtil;

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
    public function calcultateName($value, $isNew = false, $name = null, array $context = [])
    {
        $fields = $this->agenda->getFieldLabels();

        $output = [];
        if (isset($context['gaf_filter_text1'], $fields[$context['gaf_filter_text1']], $context['gaf_filter_text2']) &&
                $context['gaf_filter_text1'] && $context['gaf_filter_text2']) {
            $output[] = sprintf($this->_('%s like "%s"'), $fields[$context['gaf_filter_text1']], $context['gaf_filter_text2']);
        }
        if (isset($context['gaf_filter_text3'], $fields[$context['gaf_filter_text3']], $context['gaf_filter_text4']) &&
                $context['gaf_filter_text3'] && $context['gaf_filter_text4']) {
            $output[] = sprintf($this->_('%s like "%s"'), $fields[$context['gaf_filter_text3']], $context['gaf_filter_text4']);
        }

        if ($output) {
            return ucfirst(implode($this->_(', '), $output));
        } else {
            return $this->_('empty filter');
        }
    }

    /**
     * Get the field names in appontments with their labels as the value
     *
     * @return array fieldname => label
     */
    public function getFieldLabels()
    {
        $output = [
            'gap_id_organization' => $this->_('Organization'),
            'gap_source'          => $this->_('Source of appointment'),
            'gap_id_attended_by'  => $this->_('With'),
            'gap_id_referred_by'  => $this->_('Referrer'),
            'gap_id_activity'     => $this->_('Activity'),
            'gap_id_procedure'    => $this->_('Procedure'),
            'gap_id_location'     => $this->_('Location'),
            'gap_subject'         => $this->_('Subject'),
        ];

        asort($output);

        return $output;
    }

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass()
    {
        return 'FieldLikeAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('Field match filter');
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
        $fields = $this->agenda->getFieldLabels();
        $description = sprintf($this->_(
                "Use the %%-sign to search for zero or more random characters and an _ for a single random character."
                ));

        return [
            'gaf_filter_text1' => [
                'label'        => $this->_('Field 1'),
                'multiOptions' => $fields,
                'required'     => true,
            ],
            'gaf_filter_text2' => [
                'label'        => $this->_('Search text 1'),
                'description'  => $description,
                'required'     => true,
            ],
            'gaf_filter_text3' => [
                'label'        => $this->_('Field 2'),
                'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $fields,
            ],
            'gaf_filter_text4' => [
                'label'       => $this->_('Search text 2'),
                'description' => sprintf($this->_("Required when filled - use %%-sign as well.")),
            ],
        ];
    }
}
