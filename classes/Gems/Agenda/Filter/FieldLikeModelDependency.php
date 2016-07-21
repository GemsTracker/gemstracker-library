<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SqlLikeModelDependency.php $
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
class FieldLikeModelDependency extends FilterModelDependencyAbstract
{
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
        $fields = $this->agenda->getFieldLabels();

        $output = array();
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
        $output = array(
            'gap_id_organization' => $this->_('Organization'),
            'gap_source'          => $this->_('Source of appointment'),
            'gap_id_attended_by'  => $this->_('With'),
            'gap_id_referred_by'  => $this->_('Referrer'),
            'gap_id_activity'     => $this->_('Activity'),
            'gap_id_procedure'    => $this->_('Procedure'),
            'gap_id_location'     => $this->_('Location'),
            'gap_subject'         => $this->_('Subject'),
        );
        
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
        $description = $this->_(
                "Use the %%-sign to search for zero or more random characters and an _ for a single random character."
                );

        return array(
            'gaf_filter_text1' => array(
                'label'        => $this->_('Field 1'),
                'multiOptions' => $fields,
                'required'     => true,
                ),
            'gaf_filter_text2' => array(
                'label'        => $this->_('Search text 1'),
                'description'  => $description,
                'required'     => true,
                ),
            'gaf_filter_text3' => array(
                'label'        => $this->_('Field 2'),
                'multiOptions' => $this->util->getTranslated()->getEmptyDropdownArray() + $fields,
                ),
            'gaf_filter_text4' => array(
                'label'       => $this->_('Search text 2'),
                'description' => sprintf($this->_("Required when filled - use %%-sign as well.")),
                ),
            );
    }
}
