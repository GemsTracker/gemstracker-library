<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\Filter\AppointmentFilterInterface;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\AppointmentModel;
use Gems\Snippets\ModelTableSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\TableElement;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class CalendarTableSnippet extends ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems\Agenda\Filter\AppointmentFilterInterface|array|false
     */
    protected $calSearchFilter;

    public $extraSort = ['gap_admission_time' => SORT_ASC];

    protected ?DataReaderInterface $model = null;

    protected string $onEmptyAlt = '';

    /**
     * Enable browsing, so we have a query limit. This ensures we don't try to
     * show *all* appointments on this page.
     */
    public $browse = true;

    /**
     * Don't run the count query, we don't need it.
     */
    public $showTotal = false;

    public function __construct(SnippetOptions $snippetOptions,
                                RequestInfo $requestInfo,
                                MenuSnippetHelper $menuHelper,
                                TranslatorInterface $translate,
                                protected Model $modelLoader
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);

        if ($this->onEmptyAlt) {
            $this->onEmpty = $this->onEmptyAlt;
        }
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $bridge->gr2o_id_organization;

        $keys = $this->getRouteMaps($dataModel->getMetaModel());

        $appointmentHref = $this->menuHelper->getLateRouteUrl('respondent.appointments.show', $keys, $bridge);
        $appointmentButton = isset($appointmentHref['url']) ? Html::actionLink($appointmentHref['url'], $this->_('Show appointment')) : null;

        $respondentHref = $this->menuHelper->getLateRouteUrl('respondent.show', $keys, $bridge);
        $respondentButton = isset($respondentHref['url']) ? Html::actionLink($respondentHref['url'], $this->_('Show respondent')) : null;

        $br = Html::create('br');
        $sp = Html::raw(' ');

        $table = $bridge->getTable();
        $table->appendAttrib('class', 'calendar');
        $table->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);

        // Row with dates and patient data
        $table->tr(array('onlyWhenChanged' => true, 'class' => 'date'));
        $bridge->addSortable('date_only', $this->_('Date'), array('class' => 'date'))->colspan = 4;

        // Row with dates and patient data
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'time middleAlign'));
        $td = $bridge->addSortable('gap_admission_time');
        $td->append(' ');
        $td->i(['class' => 'fa fa-stopwatch']);
        $td->title = $bridge->date_only; // Add title, to make sure row displays when time is same as time for previous day
        $bridge->addSortable('gor_name');
        $bridge->addSortable('glo_name')->colspan = 2;

        $bridge->tr()->class = array('odd', $bridge->row_class);
        $bridge->addColumn($appointmentButton)->class = 'middleAlign';
        $bridge->addMultiSort('gr2o_patient_nr', $sp, 'gap_subject', $br, 'name');
        // $bridge->addColumn(array($bridge->gr2o_patient_nr, $br, $bridge->name));
        $bridge->addMultiSort(array($this->_('With')), array(' '), 'gas_name', $br, 'gaa_name', array(' '), 'gapr_name');
        // $bridge->addColumn(array($bridge->gaa_name, $br, $bridge->gapr_name));
        $bridge->addColumn($respondentButton)->class = 'middleAlign rightAlign';

        unset($table[TableElement::THEAD]);
    }

    protected function createModel(): DataReaderInterface
    {
        if (null !== $this->calSearchFilter) {
            $this->bridgeMode = BridgeInterface::MODE_ROWS;
            $this->caption    = $this->_('Example appointments');

            if ($this->calSearchFilter instanceof AppointmentFilterInterface) {
                $this->searchFilter = [
                    \MUtil\Model::SORT_DESC_PARAM => 'gap_admission_time',
                    $this->calSearchFilter->getSqlAppointmentsWhere(),
                    'limit' => 10,
                ];
                // \MUtil\EchoOut\EchoOut::track($this->calSearchFilter->getSqlAppointmentsWhere());

                $this->onEmpty = $this->_('No example appointments found');
            } elseif (false === $this->calSearchFilter) {
                $this->onEmpty = $this->_('Filter is inactive');
                $this->searchFilter = ['1=0'];
            } elseif (is_array($this->calSearchFilter)) {
                $this->searchFilter = $this->calSearchFilter;
            }
        }

        if (! $this->model instanceof AppointmentModel) {
            $this->model = $this->modelLoader->createAppointmentModel();
            $this->model->applyBrowseSettings();
        }
        $this->model->addColumn("CONVERT(gap_admission_time, DATE)", 'date_only');
        $this->model->getMetaModel()->set('date_only', ['type' => MetaModelInterface::TYPE_DATE]);
        $this->model->getMetaModel()->set('gap_admission_time', [
            'label' => $this->_('Time'),
            'type' => MetaModelInterface::TYPE_TIME
        ]);

        $this->model->getMetaModel()->set('gr2o_patient_nr', ['label' => $this->_('Respondent nr')]);

        Model\Respondent\RespondentModel::addNameToModel($this->model->getMetaModel(), $this->_('Name'));

        $this->model->applyMask();

        return $this->model;
    }

    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        $output = parent::getRouteMaps($metaModel);
        $output[MetaModelInterface::REQUEST_ID1] = 'gr2o_patient_nr';
        $output[MetaModelInterface::REQUEST_ID2] = 'gr2o_id_organization';
        return $output;
    }
}
