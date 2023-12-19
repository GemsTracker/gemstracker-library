<?php

/**
 *
 * @package    Gems
 * @subpackage AppointmentFilterModelAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda;

use Gems\Db\ResultFetcher;
use Gems\Html;
use Gems\Menu\RouteHelper;
use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentFilterModelAbstract
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 13:07:11
 */
class AppointmentFilterModel extends GemsJoinModel
{
    /**
     * The filter dependency class names, the parts after *_Agenda_Filter_
     *
     * @var array (dependencyClassName)
     */
    protected array $filterDependencies = [
        'ActProcModelDependency',
        'AndModelDependency',
        'DiagnosisEpisodeModelDependency',
        'FieldLikeModelDependency',
        'JsonDiagnosisDependency',
        'LocationModelDependency',
        'OrganizationModelDependency',
        'OrModelDependency',
        'SubjectAppointmentModelDependency',
        'SubjectEpisodeModelDependency',
        'WithModelDependency',
        'XandModelDependency',
        'XorModelDependency',
        ];

    /**
     * The filter class names, loaded by loodFilterDependencies()
     *
     * @var array filterClassName => Label
     */
    protected array $filterOptions;

    /**
     * The sub filter dependency class names, needed separately for
     *
     * @var array dependencyClassName => description
     */
    protected $subFilters = [];

    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected Agenda $agenda,
        protected ResultFetcher $resultFetcher,
        protected RouteHelper $routeHelper,
        protected Translated $translatedUtil,
    ) {
        parent::__construct('gems__appointment_filters', $this->metaModelLoader, $sqlRunner, $translate);

        $this->metaModelLoader->setChangeFields($this->metaModel, 'gaf');

        $this->translate = $translate;
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return self
     */
    public function applyBrowseSettings()
    {
        $this->loadFilterDependencies(false);

        $yesNo = $this->translatedUtil->getYesNo();

        $this->metaModel->set('gaf_class', [
            'label' => $this->_('Filter type'),
                'description' => $this->_('Determines what is filtered how.'),
                'multiOptions' => $this->filterOptions
                ]);
        $this->addColumn('COALESCE(gaf_manual_name, gaf_calc_name)', 'gaf_name');
        $this->metaModel->set('gaf_name', ['label' => $this->_('Name')]);
        $this->metaModel->set('gaf_id_order', [
            'label' => $this->_('Order'),
                'description' => $this->_('Execution order of the filters, lower numbers are executed first.')
                ]);
        $this->metaModel->set('gaf_active', [
            'label' => $this->_('Active'),
                'multiOptions' => $yesNo
                ]);

        $this->addColumn("(SELECT COUNT(*) FROM gems__track_appointments WHERE gaf_id = gtap_filter_id)", 'usetrack');
        $this->metaModel->set('usetrack', [
            'label' => $this->_('Use in track fields'),
                'description' => $this->_('The number of uses of this filter in track fields.'),
                'elementClass' => 'Exhibitor'
                ]);
        $this->addColumn($this->getSubFilterSql('COUNT(*)'), 'usefilter');
        $this->metaModel->set('usefilter', [
            'label' => $this->_('Use in filters'),
                'description' => $this->_('The number of uses of this filter in other filters.'),
                'elementClass' => 'Exhibitor'
                ]);

        $this->addColumn("CASE WHEN gaf_active =  1 THEN '' ELSE 'deleted' END", 'row_class');

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return self
     */
    public function applyDetailSettings()
    {
        $this->loadFilterDependencies(true);

        $yesNo = $this->translatedUtil->getYesNo();

        $this->metaModel->resetOrder();

        $this->metaModel->set('gaf_class', [
            'label' => $this->_('Filter type'),
                'description' => $this->_('Determines what is filtered how.'),
                'multiOptions' => $this->filterOptions
                ]);
        $this->metaModel->set('gaf_manual_name', [
            'label' => $this->_('Manual name'),
                'description' => $this->_('A name for this filter. The calculated name is used otherwise.')
        ]);
        $this->metaModel->set('gaf_calc_name', ['label' => $this->_('Calculated name')]);
        $this->metaModel->set('gaf_id_order', [
            'label' => $this->_('Order'),
                'description' => $this->_('Execution order of the filters, lower numbers are executed first.')
                ]);

        // Set the order
        $this->metaModel->set('gaf_filter_text1');
        $this->metaModel->set('gaf_filter_text2');
        $this->metaModel->set('gaf_filter_text3');
        $this->metaModel->set('gaf_filter_text4');

        $this->metaModel->set('gaf_active', [
            'label' => $this->_('Active'),
                'multiOptions' => $yesNo
                ]);

        $this->addColumn('NULL', 'usetrack');
        $this->metaModel->setOnLoad('usetrack', [$this, 'loadTracks']);
        $this->metaModel->set('usetrack', [
            'label' => $this->_('Use in track fields'),
                'description' => $this->_('The use of this filter in track fields.'),
                'elementClass' => 'Exhibitor',
                'formatFunction' => [$this, 'showTracks']
                ]);

        $this->addColumn('NULL', 'usefilter');
        $this->metaModel->setOnLoad('usefilter', [$this, 'loadFilters']);
        $this->metaModel->set('usefilter', [
            'label' => $this->_('Use in filters'),
                'description' => $this->_('The use of this filter in other filters.'),
                'elementClass' => 'Exhibitor',
                'formatFunction' => [$this, 'showFilters']
                ]);

        $this->addColumn("CASE WHEN gaf_active =  1 THEN '' ELSE 'deleted' END", 'row_class');

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return self
     */
    public function applyEditSettings($create = false)
    {
        $this->applyDetailSettings();

        reset($this->filterOptions);
        $default = key($this->filterOptions);
        $this->metaModel->set('gaf_class', [
            'autoSubmit' => true,
            'default' =>  $default,
            ]);

        // gaf_id is not needed for some validators
        $this->metaModel->set('gaf_id', ['elementClass' => 'Hidden']);

        $this->metaModel->set('gaf_id_order', ['filters[digits]' => 'Digits']);

        $this->metaModel->set('gaf_calc_name', ['elementClass' => 'Exhibitor']);
        $this->metaModel->setOnSave('gaf_calc_name', [$this, 'calcultateName']);
        $this->metaModel->set('gaf_active', ['elementClass' => 'Checkbox']);

        if ($create) {
            $default = $this->resultFetcher->fetchOne("SELECT MAX(gaf_id_order) FROM gems__appointment_filters");
            $this->metaModel->set('gaf_id_order', ['default' => (int) $default + 10]);
        }

        return $this;
    }

    /**
     * Get an SQL query for the filters using a specific other filter
     *
     * @param int $filterId
     * @param string $cols Columns to select
     * @return string
     */
    public function getSubFilterIdSql($filterId, $cols = 'other.gaf_id')
    {
        $dependencies = implode("', '", array_keys($this->getSubFilters()));
        $id           = (int) $filterId;

        return "SELECT $cols
                    FROM gems__appointment_filters AS other
                    WHERE other.gaf_class IN ('$dependencies') AND
                        (
                            other.gaf_filter_text1 = $id OR
                            other.gaf_filter_text2 = $id OR
                            other.gaf_filter_text3 = $id OR
                            other.gaf_filter_text4 = $id
                        )";
    }

    /**
     * Get an SQL query for the filters using another filter
     *
     * @param string $cols Columns to select
     * @param string $parentTable Table name used in main query
     * @return string
     */
    public function getSubFilterSql($cols = '*', $parentTable = 'gems__appointment_filters')
    {
        $dependencies = implode("', '", array_keys($this->getSubFilters()));
        return "(SELECT $cols
                    FROM gems__appointment_filters AS other
                    WHERE other.gaf_class IN ('$dependencies') AND
                        (
                            $parentTable.gaf_id = other.gaf_filter_text1 OR
                            $parentTable.gaf_id = other.gaf_filter_text2 OR
                            $parentTable.gaf_id = other.gaf_filter_text3 OR
                            $parentTable.gaf_id = other.gaf_filter_text4
                        )
                )";
    }

    /**
     * The sub filter dependency class names, needed separately for
     *
     * @return array dependencyClassName => description
     */
    public function getSubFilters()
    {
        $this->loadFilterDependencies(true);

        return $this->subFilters;
    }

    /**
     * Load filter dependencies into model and populate the filterOptions
     *
     * @param boolean $activateDependencies When true, adds dependecies to model
     * @return array filterClassName => Label
     */
    protected function loadFilterDependencies($activateDependencies = true)
    {
        if (! isset($this->filterOptions)) {
            $maxLength = (int) $this->metaModel->get('gaf_calc_name', 'maxlength');

            $this->filterOptions = array();
            foreach ($this->filterDependencies as $dependencyClass) {
                $dependency = $this->agenda->newFilterObject($dependencyClass);
                if ($dependency instanceof FilterModelDependencyAbstract) {

                    $this->filterOptions[$dependency->getFilterClass()] = $dependency->getFilterName();

                    if ($dependency instanceof SubFilterDependencyInterface) {
                        $this->subFilters[$dependency->getFilterClass()] = $dependency->getFilterName();
                    }

                    if ($activateDependencies) {
                        $dependency->setMaximumCalcLength($maxLength);
                        $this->metaModel->addDependency($dependency);
                    }
                }
            }
            asort($this->filterOptions);
        }

        return $this->filterOptions;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns an paired array of filters
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return array [filterId => filter name]
     */
    public function loadFilters($value, $isNew = false, $name = null, array $context = array()): array
    {
        if ($isNew || (! isset($context['gaf_id']))) {
            return [];
        }
        $output       = $this->resultFetcher->fetchPairs($this->getSubFilterIdSql(
                $context['gaf_id'],
                "gaf_id, COALESCE(gaf_manual_name, gaf_calc_name) AS used_name"
                ));

        if ($output) {
            return $output;
        }

        return [];
    }

    /**
     * A ModelAbstract->setOnSave() function that a nested array containing the tracks and fields using
     * this filter
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return array
     */
    public function loadTracks($value, $isNew = false, $name = null, array $context = array()): array
    {
        if ($isNew || (! isset($context['gaf_id']))) {
            return [];
        }
        return $this->resultFetcher->fetchAll(
                "SELECT gtr_id_track, gtr_track_name, gtap_id_app_field, gtap_field_name
                    FROM gems__track_appointments INNER JOIN gems__tracks ON gtap_id_track = gtr_id_track
                    WHERE gtap_filter_id = ?",
                [$context['gaf_id']]);
    }

    /**
     *
     * @param array $value
     * @return mixed
     */
    public function showFilters($value)
    {
        if (! ($value && is_array($value))) {
            return Html::create('em', $this->_('Not used in filters'));
        }

        $showMenu = (bool) $this->routeHelper->hasAccessToRoute('setup.agenda.filter.show');
        $list = Html::create('ol');
        foreach ($value as $id => $label) {
            $li = $list->li();

            if ($showMenu) {
                $li->em()->a(
                    $this->routeHelper->getRouteUrl('setup.agenda.filter.show', [\MUtil\Model::REQUEST_ID => intval($id)]),
                    $label
                    );
            } else {
                $li->em($label);
            }
        }

        return $list;
    }

    /**
     *
     * @param array $value
     * @return mixed
     */
    public function showTracks($value)
    {
        if (! ($value && is_array($value))) {
            return Html::create('em', $this->_('Not used in tracks'));
        }

        $list = Html::create('ol');
        foreach ($value as $row) {
            $li = $list->li();

            $trackUrl = $this->routeHelper->getRouteUrl(
                'track-builder.track-maintenance.show',
                ['trackId' => $row['gtr_id_track']]
            );

            $trackFieldUrl = $this->routeHelper->getRouteUrl(
                'track-builder.track-maintenance.track-fields.show',
                [
                    'trackId' => $row['gtr_id_track'],
                    \Gems\Model::FIELD_ID => $row['gtap_id_app_field'],
                    'sub' => FieldMaintenanceModel::APPOINTMENTS_NAME
                ],
            );

            if ($trackUrl) {
                $li->em()->a(
                    $trackUrl,
                    $row['gtr_track_name']
                );
            } else {
                $li->em($row['gtr_track_name']);
            }
            $li->append($this->_(': '));

            if ($trackFieldUrl) {
                $li->em()->a(
                    $trackFieldUrl,
                    $row['gtap_field_name']
                );
            } else {
                $li->em($row['gtap_field_name']);
            }
        }

        return $list;
    }
}
