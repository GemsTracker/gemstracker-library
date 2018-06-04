<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionModel extends \MUtil_Model_TableModel
{
/**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;
    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The filter dependency class names, the parts after *_Agenda_Filter_
     *
     * @var array (dependencyClassName)
     */
    protected $filterDependencies = array(
        'AndModelDependency',
        'FieldLikeModelDependency',
        'LocationModelDependency',
        'NotAnyModelDependency',
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
    public function __construct($name = 'conditions')
    {
        parent::__construct($name, 'gems__conditions', 'gcon');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyBrowseSettings()
    {
        $conditions = $this->loader->getConditions();
        
        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->set('gcon_type', 'label', $this->_('Type'),
                'description', $this->_('Determines where the condition can be applied.'),
                'multiOptions', $conditions->getConditionTypes()
                );
        $this->set('gcon_class', 'label', $this->_('Condition'),
                'multiOptions', []
                );
        
        $this->set('gcon_name', 'label', $this->_('Name'));
        $this->set('gcon_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );
                
        $this->addDependency('Condition\\\TypeDependency');

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

        $this->set('gcon_class', 'label', $this->_('Filter type'),
                'description', $this->_('Determines what is filtered how.'),
                'multiOptions', $this->filterOptions
                );
        $this->set('gcon_manual_name', 'label', $this->_('Manual name'),
                'description', $this->_('A name for this filter. The calculated name is used otherwise.'));
        $this->set('gcon_calc_name', 'label', $this->_('Calculated name'));
        $this->set('gcon_id_order', 'label', $this->_('Order'),
                'description', $this->_('Execution order of the filters, lower numbers are executed first.')
                );

        // Set the order
        $this->set('gcon_filter_text1');
        $this->set('gcon_filter_text2');
        $this->set('gcon_filter_text3');
        $this->set('gcon_filter_text4');

        $this->set('gcon_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addColumn(new \Zend_Db_Expr(sprintf(
                "(SELECT COALESCE(GROUP_CONCAT(gtr_track_name, '%s', gtap_field_name
                                    ORDER BY gtr_track_name, gtap_id_order SEPARATOR '%s'), '%s')
                    FROM gems__track_appointments INNER JOIN gems__tracks ON gtap_id_track = gtr_id_track
                    WHERE gcon_id = gtap_filter_id)",
                $this->_(': '),
                $this->_('; '),
                $this->_('Not used in tracks')
                )), 'usetrack');
        $this->set('usetrack', 'label', $this->_('Use in track fields'),
                'description', $this->_('The use of this filter in track fields.'),
                'elementClass', 'Exhibitor'
                );
        $this->addColumn(new \Zend_Db_Expr(sprintf(
                "(SELECT COALESCE(GROUP_CONCAT(gcon_calc_name ORDER BY gcon_id_order SEPARATOR '%s'), '%s')
                    FROM gems__appointment_filters AS other
                    WHERE gcon_class IN ('AndAppointmentFilter', 'OrAppointmentFilter') AND
                        (
                            gems__appointment_filters.gcon_id = other.gcon_filter_text1 OR
                            gems__appointment_filters.gcon_id = other.gcon_filter_text2 OR
                            gems__appointment_filters.gcon_id = other.gcon_filter_text3 OR
                            gems__appointment_filters.gcon_id = other.gcon_filter_text4
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
        $this->set('gcon_class',         'default', $default, 'onchange', 'this.form.submit();');

        // gcon_id is not needed for some validators
        $this->set('gcon_id',            'elementClass', 'Hidden');

        $this->set('gcon_calc_name',     'elementClass', 'Exhibitor');
        $this->setOnSave('gcon_calc_name', array($this, 'calcultateName'));
        $this->set('gcon_active',        'elementClass', 'Checkbox');

        if ($create) {
            $default = $this->db->fetchOne("SELECT MAX(gcon_id_order) FROM gems__appointment_filters");
            $this->set('gcon_id_order', 'default', intval($default) + 10);
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
            $maxLength = $this->get('gcon_calc_name', 'maxlength');

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
