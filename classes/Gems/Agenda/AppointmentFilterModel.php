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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage AppointmentFilterModelAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentFilterModel.php $
 */

namespace Gems\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentFilterModelAbstract
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 13:07:11
 */
class AppointmentFilterModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The filter dependency class names, the parts after *_Agenda_Filter_
     *
     * @var array (dependencyClassName)
     */
    protected $filterDependencies = array(
        'AndModelDependency',
        'FieldLikeModelDependency',
        'LocationModelDependency',
        'OrModelDependency',
        'SqlLikeModelDependency',
        'SubjectModelDependency',
    );

    /**
     * The filter class names, loaded by loodFilterDependencies()
     *
     * @var array filterClassName => Label
     */
    protected $filterOptions;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $name
     */
    public function __construct($name = 'app-filter')
    {
        parent::__construct($name, 'gems__appointment_filters', 'gaf');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyBrowseSettings()
    {
        $this->loadFilterDependencies(false);

        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->set('gaf_class', 'label', $this->_('Filter type'),
                'description', $this->_('Determines what is filtered how.'),
                'multiOptions', $this->filterOptions
                );
        $this->addColumn('COALESCE(gaf_manual_name, gaf_calc_name)', 'gaf_name');
        $this->set('gaf_name', 'label', $this->_('Name'));
        $this->set('gaf_id_order', 'label', $this->_('Order'),
                'description', $this->_('Execution order of the filters, lower numbers are executed first.')
                );
        $this->set('gaf_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addColumn(new \Zend_Db_Expr(
                "(SELECT COUNT(*) FROM gems__track_appointments WHERE gaf_id = gtap_filter_id)"
                ), 'usetrack');
        $this->set('usetrack', 'label', $this->_('Use in track fields'),
                'description', $this->_('The number of uses of this filter in track fields.'),
                'elementClass', 'Exhibitor'
                );
        $this->addColumn(new \Zend_Db_Expr(
                "(SELECT COUNT(*)
                    FROM gems__appointment_filters AS other
                    WHERE gaf_class IN ('AndAppointmentFilter', 'OrAppointmentFilter') AND
                        (
                            gems__appointment_filters.gaf_id = other.gaf_filter_text1 OR
                            gems__appointment_filters.gaf_id = other.gaf_filter_text2 OR
                            gems__appointment_filters.gaf_id = other.gaf_filter_text3 OR
                            gems__appointment_filters.gaf_id = other.gaf_filter_text4
                        )
                )"
                ), 'usefilter');
        $this->set('usefilter', 'label', $this->_('Use in filters'),
                'description', $this->_('The number of uses of this filter in other filters.'),
                'elementClass', 'Exhibitor'
                );


        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyDetailSettings()
    {
        $this->loadFilterDependencies(true);

        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->resetOrder();

        $this->set('gaf_class', 'label', $this->_('Filter type'),
                'description', $this->_('Determines what is filtered how.'),
                'multiOptions', $this->filterOptions
                );
        $this->set('gaf_manual_name', 'label', $this->_('Manual name'),
                'description', $this->_('A name for this filter. The calculated name is used otherwise.'));
        $this->set('gaf_calc_name', 'label', $this->_('Calculated name'));
        $this->set('gaf_id_order', 'label', $this->_('Order'),
                'description', $this->_('Execution order of the filters, lower numbers are executed first.')
                );

        // Set the order
        $this->set('gaf_filter_text1');
        $this->set('gaf_filter_text2');
        $this->set('gaf_filter_text3');
        $this->set('gaf_filter_text4');

        $this->set('gaf_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addColumn(new \Zend_Db_Expr(sprintf(
                "(SELECT COALESCE(GROUP_CONCAT(gtr_track_name, '%s', gtap_field_name
                                    ORDER BY gtr_track_name, gtap_id_order SEPARATOR '%s'), '%s')
                    FROM gems__track_appointments INNER JOIN gems__tracks ON gtap_id_track = gtr_id_track
                    WHERE gaf_id = gtap_filter_id)",
                $this->_(': '),
                $this->_('; '),
                $this->_('Not used in tracks')
                )), 'usetrack');
        $this->set('usetrack', 'label', $this->_('Use in track fields'),
                'description', $this->_('The use of this filter in track fields.'),
                'elementClass', 'Exhibitor'
                );
        $this->addColumn(new \Zend_Db_Expr(sprintf(
                "(SELECT COALESCE(GROUP_CONCAT(gaf_calc_name ORDER BY gaf_id_order SEPARATOR '%s'), '%s')
                    FROM gems__appointment_filters AS other
                    WHERE gaf_class IN ('AndAppointmentFilter', 'OrAppointmentFilter') AND
                        (
                            gems__appointment_filters.gaf_id = other.gaf_filter_text1 OR
                            gems__appointment_filters.gaf_id = other.gaf_filter_text2 OR
                            gems__appointment_filters.gaf_id = other.gaf_filter_text3 OR
                            gems__appointment_filters.gaf_id = other.gaf_filter_text4
                        )
                )",
                $this->_('; '),
                $this->_('Not used in filters')
                )), 'usefilter');
        $this->set('usefilter', 'label', $this->_('Use in filters'),
                'description', $this->_('The use of this filter in other filters.'),
                'elementClass', 'Exhibitor'
                );

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyEditSettings($create = false)
    {
        $this->applyDetailSettings();

        reset($this->filterOptions);
        $default = key($this->filterOptions);
        $this->set('gaf_class',         'default', $default, 'onchange', 'this.form.submit();');

        // gaf_id is not needed for some validators
        $this->set('gaf_id',            'elementClass', 'Hidden');

        $this->set('gaf_calc_name',     'elementClass', 'Exhibitor');
        $this->setOnSave('gaf_calc_name', array($this, 'calcultateName'));
        $this->set('gaf_active',        'elementClass', 'Checkbox');

        if ($create) {
            $default = $this->db->fetchOne("SELECT MAX(gaf_id_order) FROM gems__appointment_filters");
            $this->set('gaf_id_order', 'default', intval($default) + 10);
        }

        return $this;
    }

    /**
     * Load filter dependencies into model and populate the filterOptions
     *
     * @return array filterClassName => Label
     */
    protected function loadFilterDependencies($activateDependencies = true)
    {
        if (! $this->filterOptions) {
            $maxLength = $this->get('gaf_calc_name', 'maxlength');

            $this->filterOptions = array();
            foreach ($this->filterDependencies as $dependencyClass) {
                $dependency = $this->agenda->newFilterObject($dependencyClass);
                if ($dependency instanceof FilterModelDependencyAbstract) {

                    $this->filterOptions[$dependency->getFilterClass()] = $dependency->getFilterName();

                    if ($activateDependencies) {
                        $dependency->setMaximumCalcLength($maxLength);
                        $this->addDependency($dependency);
                    }
                }
            }
            asort($this->filterOptions);
        }

        return $this->filterOptions;
    }
}
