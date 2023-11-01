<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Legacy\CurrentUserRepository;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\AppointmentModel;
use Gems\Tracker\Respondent;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentsTableSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * Date storage format string
     *
     * @var string
     */
    private $_dateStorageFormat;

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['gap_admission_time' => SORT_DESC];

    /**
     * Image for time display
     *
     * @var \Zalt\Html\HtmlElement
     * /
    private $_timeImg;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'appointment';

    /**
     * Menu routes or routeparts to show in Edit box.
     *
     * @var array (int/label => route or routepart)
     */
    protected  array $menuEditRoutes = ['respondent.appointments.edit'];

    /**
     * Menu routes or routeparts to show in Show box.
     *
     * @var array (int/label => route or routepart)
     */
    protected array $menuShowRoutes = ['respondent.appointments.show'];

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected Model $modelLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->onEmpty = $this->_('No appointments found.');
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
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;

        $keys = $this->getRouteMaps($dataModel->getMetaModel());

        $episode = $this->currentUser->hasPrivilege('pr.respondent.episodes-of-care.index');

        $br      = Html::create('br');

        $table   = $bridge->getTable();
        $table->appendAttrib('class', 'calendar');
        $bridge->tr()->appendAttrib('class', $bridge->row_class);

        if ($this->showMenu) {
            foreach ($this->getShowUrls($bridge, $keys, $bridge) as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Show');
                }
                $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
            }
        }
        if ($this->sortableLinks) {
            $bridge->addMultiSort([$bridge->date_only], $br, 'gap_admission_time')->class = 'date';
            if ($episode) {
                $bridge->addMultiSort('gap_id_episode');
            }
            $bridge->addMultiSort('gap_subject', $br, 'gas_name');
            $bridge->addMultiSort('gaa_name', $br, 'gapr_name');
            $bridge->addMultiSort('gor_name', $br, 'glo_name');
        } else {
            $bridge->addMultiSort(
                [$bridge->date_only],
                $br,
                [$bridge->gap_admission_time, $dataModel->get('gap_admission_time', 'label')]
            );
            if ($episode) {
                $bridge->addMultiSort([$bridge->gap_id_episode, $dataModel->get('gap_id_episode', 'label')]);
            }
            $bridge->addMultiSort(
                [$bridge->gap_subject, $dataModel->get('gap_subject', 'label')],
                $br,
                [$bridge->gas_name, $dataModel->get('gas_name', 'label')]
            );
            $bridge->addMultiSort(
                [$bridge->gaa_name, $dataModel->get('gaa_name', 'label')],
                $br,
                [$bridge->gapr_name, $dataModel->get('gapr_name', 'label')]
            );
            $bridge->addMultiSort(
                [$bridge->gor_name, $dataModel->get('gor_name', 'label')],
                $br,
                [$bridge->glo_name, $dataModel->get('glo_name', 'label')]
            );
        }
        if ($this->showMenu) {
            foreach ($this->getEditUrls($bridge, $keys, $bridge) as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Show');
                }
                $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if ($this->model instanceof AppointmentModel) {
            $model = $this->model;
        } else {
            $model = $this->modelLoader->createAppointmentModel();
            $model->applyBrowseSettings();
        }

        $model->addColumn(new \Zend_Db_Expr("CONVERT(gap_admission_time, DATE)"), 'date_only');
        $model->set('date_only', 'formatFunction', [$this, 'formatDate']);
        $model->set('gap_admission_time', 'label', $this->_('Time'),
            'formatFunction', [$this, 'formatTime']);

        $this->_dateStorageFormat = $model->get('gap_admission_time', 'storageFormat');

        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        if ($this->respondent instanceof Respondent) {
            $model->addFilter([
                'gap_id_user' => $this->respondent->getId(),
                'gap_id_organization' => $this->respondent->getOrganizationId(),
            ]);
        }

        return $model;
    }

    /**
     * Display the date field
     *
     * @param mixed $value
     */
    public function formatDate($value)
    {
        return Html::create(
            'span',
            // array('class' => 'date'),
            \MUtil\Model::reformatDate($value, 'Y-m-d', 'j M Y')
        );
    }

    /**
     * Display the time field
     *
     * @param mixed $value
     */
    public function formatTime($value)
    {
        return Html::create(
            'span',
            ' ',
            // array('class' => 'time'),
            // $this->_timeImg,
            \MUtil\Model::reformatDate($value, $this->_dateStorageFormat, 'H:i')
        );
    }

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter = parent::getFilter($metaModel);

        if ($this->respondent instanceof Respondent) {
            $filter['gap_id_user'] = $this->respondent->getId();
            $filter['gap_id_organization'] = $this->respondent->getOrganizationId();
        }

        $episodeId = $this->requestInfo->getParam(Model::EPISODE_ID);
        if ($episodeId) {
            $this->caption = $this->_('Linked appointments');
            $this->onEmpty = $this->_('No linked appointments found.');
            $filter['gap_id_episode'] = $episodeId;
        }

        return $filter;
    }

    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        $output = parent::getRouteMaps($metaModel);
        $output[\MUtil\Model::REQUEST_ID1] = 'gr2o_patient_nr';
        $output[\MUtil\Model::REQUEST_ID2] = 'gr2o_id_organization';
        return $output;
    }
}
