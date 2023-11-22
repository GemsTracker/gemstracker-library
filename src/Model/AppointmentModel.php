<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Agenda\Agenda;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Model;
use Gems\User\Mask\MaskRepository;
use Gems\Util;
use MUtil\Model\Type\JsonData;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlElement;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Model\Type\JsonType;

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentModel extends GemsMaskedModel
{
    /**
     * The number of tokens changed by the last save
     *
     * @var int
     */
    protected $_changedTokenCount = 0;

    /**
     *
     * @var \Gems\User\Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var boolean
     */
    protected $autoTrackUpdate = true;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     * Self constructor
     */
    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected Agenda $agenda,
        CurrentUserRepository $currentUserRepository,
        MaskRepository $maskRepository,
        protected readonly Util $util,
        protected readonly Util\Translated $translatedUtil,
    ) {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('gems__appointments', $this->metaModelLoader, $sqlRunner, $translate, $maskRepository);

        $this->metaModelLoader->setChangeFields($this->metaModel, 'gap');

        $this->currentUser = $currentUserRepository->getCurrentUser();

        $this->addTable(
            'gems__respondent2org',
            array('gap_id_user' => 'gr2o_id_user', 'gap_id_organization' => 'gr2o_id_organization'),
            false,
        );

        $this->addColumn("'appointment'", Model::ID_TYPE);
        $this->metaModel->setKeys([Model::APPOINTMENT_ID => 'gap_id_appointment']);

        $this->addColumn(
            "CASE WHEN gap_status IN ('" .
            implode("', '", $this->agenda->getStatusKeysInactive()) .
            "') THEN 'deleted' ELSE '' END",
            'row_class'
        );

        $codes = $this->agenda->getStatusCodesInactive();
        if (isset($codes['CA'])) {
            $cancelCode = 'CA';
        } elseif ($codes) {
            reset($codes);
            $cancelCode = key($codes);
        } else {
            $cancelCode = null;
        }
        if ($cancelCode) {
            $this->metaModel->set('gap_status', [
                ActivatingYesNoType::$activatingValue => 1,
                ActivatingYesNoType::$deactivatingValue => 0
            ]);
        }
    }

    /**
     * Add the join tables instead of lookup tables.
     */
    protected function _addJoinTables()
    {
        $this->addTable('gems__respondents', array('gap_id_user' => 'grs_id_user'));

        if ($this->metaModel->has('gap_id_organization')) {
            $this->addTable(
                'gems__organizations',
                array('gap_id_organization' => 'gor_id_organization'),
                false
            );
        }
        if ($this->metaModel->has('gap_id_attended_by')) {
            $this->addLeftTable(
                'gems__agenda_staff',
                array('gap_id_attended_by' => 'gas_id_staff'),
                false
            );
        }
        /*
        if ($this->metaModel->has('gap_id_referred_by')) {
            $this->addLeftTable(
                    array('ref_staff' => 'gems__agenda_staff'),
                    array('gap_id_referred_by' => 'ref_staff.gas_id_staff')
                    );
        } // */
        if ($this->metaModel->has('gap_id_activity')) {
            $this->addLeftTable(
                'gems__agenda_activities',
                array('gap_id_activity' => 'gaa_id_activity'),
                false
            );
        }
        if ($this->metaModel->has('gap_id_procedure')) {
            $this->addLeftTable(
                'gems__agenda_procedures',
                array('gap_id_procedure' => 'gapr_id_procedure'),
                false
            );
        }
        if ($this->metaModel->has('gap_id_location')) {
            $this->addLeftTable(
                'gems__locations',
                array('gap_id_location' => 'glo_id_location'),
                false
            );
        }
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems\Model\AppointmentModel
     */
    public function applyBrowseSettings()
    {
        $this->_addJoinTables();
        $this->metaModel->resetOrder();

        $this->metaModel->setIfExists('gap_admission_time', ['label' => $this->_('Appointment')]);
        $this->metaModel->setIfExists('gap_status', [
            'label' => $this->_('Type'),
            'multiOptions' => $this->agenda->getStatusCodes()
        ]);

        if ($this->currentUser->hasPrivilege('pr.respondent.episodes-of-care.index')) {
            $this->metaModel->setIfExists('gap_id_episode', [
                'label' => $this->_('Episode'),
                'formatFunction' => [$this, 'showEpisode']
            ]);
        }

        $this->metaModel->setIfExists('gas_name', ['label' => $this->_('With')]);
        //  $this->setIfExists('ref_staff.gas_name', ['label' => $this->_('By')]);
        $this->metaModel->setIfExists('gaa_name', ['label' => $this->_('Activities')]);
        $this->metaModel->setIfExists('gapr_name', ['label' => $this->_('Procedures')]);
        $this->metaModel->setIfExists('glo_name', ['label' => $this->_('Location')]);
        $this->metaModel->setIfExists('gor_name', ['label' => $this->_('Organization')]);
        $this->metaModel->setIfExists('gap_subject', ['label' => $this->_('Subject')]);

        $dels = $this->agenda->getStatusKeysInactiveDbQuoted();
        if ($dels) {
            $this->addColumn("CASE WHEN gap_status IN ($dels) THEN 'deleted' ELSE '' END ", 'row_class');
        }

        $this->applyMask();

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @param boolean $setMulti When false organization dependent multi options are nor filled.
     * @return \Gems\Model\AppointmentModel
     */
    public function applyDetailSettings($setMulti = true)
    {
        $this->metaModel->resetOrder();

        $dbLookup   = $this->util->getDbLookup();
        $empty      = $this->translatedUtil->getEmptyDropdownArray();

        $this->metaModel->setIfExists('gap_admission_time', [
            'label' => $this->_('Appointment'),
            'dateFormat' =>  'd-m-Y H:i',
            'description' => $this->_('dd-mm-yyyy hh:mm')
        ]);
        $this->metaModel->setIfExists('gap_discharge_time', [
            'label' => $this->_('Discharge'),
            'dateFormat' =>  'd-m-Y H:i',
            'description' => $this->_('dd-mm-yyyy hh:mm')
        ]);
        $this->metaModel->setIfExists('gap_code', [
            'label' => $this->_('Type'),
            'multiOptions' => $this->agenda->getTypeCodes()
        ]);
        $this->metaModel->setIfExists('gap_status', [
            'label' => $this->_('Status'),
            'multiOptions' => $this->agenda->getStatusCodes()
        ]);
        if ($this->currentUser->hasPrivilege('pr.respondent.episodes-of-care.index')) {
            $this->metaModel->setIfExists('gap_id_episode', [
                'label' => $this->_('Episode'),
                'required' => false
            ]);
        }

        $this->metaModel->setIfExists('gap_id_attended_by', [
            'label' => $this->_('With'),
            'multiOptions' => $empty + $this->agenda->getHealthcareStaff()
        ]);
        $this->metaModel->setIfExists('gap_id_referred_by', [
            'label' => $this->_('Referrer'),
            'multiOptions' => $empty + $this->agenda->getHealthcareStaff()
        ]);
        $this->metaModel->setIfExists('gap_id_activity', ['label' => $this->_('Activities')]);
        $this->metaModel->setIfExists('gap_id_procedure', ['label' => $this->_('Procedures')]);
        $this->metaModel->setIfExists('gap_id_location', ['label' => $this->_('Location')]);
        $this->metaModel->setIfExists('gap_id_organization', [
            'label' => $this->_('Organization'),
            'elementClass' => 'Exhibitor',
            'multiOptions' => $empty + $dbLookup->getOrganizations()
        ]);
        $this->metaModel->setIfExists('gap_subject', ['label' => $this->_('Subject')]);
        $this->metaModel->setIfExists('gap_comment', ['label' => $this->_('Comment')]);

        $jsonType = new JsonType(10);
        $jsonType->apply($this->metaModel, 'gap_info');
        $this->metaModel->setIfExists('gap_info', [
            'label' => $this->_('Additional info'),
        ]);

        if ($setMulti) {
            $this->metaModel->setIfExists('gap_id_activity', ['multiOptions' => $empty + $this->agenda->getActivities()]);
            $this->metaModel->setIfExists('gap_id_procedure', ['multiOptions' => $empty + $this->agenda->getProcedures()]);
            $this->metaModel->setIfExists('gap_id_location', ['multiOptions' => $empty + $this->agenda->getLocations()]);
        }

        $this->applyMask();

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param int $orgId The id of the current organization
     * @return \Gems\Model\AppointmentModel
     */
    public function applyEditSettings($orgId = null)
    {
        $this->applyDetailSettings(false);

        $empty  = $this->translatedUtil->getEmptyDropdownArray();

        $this->metaModel->set('gap_id_user', [
            'elementClass' => 'Hidden',
        ]);

        $this->metaModel->setIfExists('gap_id_organization', ['default' => $orgId ?: $this->currentOrganization->getId()]);
        $this->metaModel->setIfExists('gap_admission_time', ['elementClass' => 'Date']);
        $this->metaModel->setIfExists('gap_discharge_time', ['elementClass' => 'Date']);
        $this->metaModel->setIfExists('gap_status', ['required' => true]);
        $this->metaModel->setIfExists('gap_comment', ['elementClass' => 'Textarea', 'rows' => 5]);
        $this->metaModel->setIfExists('gap_info', ['elementClass' => 'Exhibitor']);

        $this->metaModel->setIfExists('gap_id_activity', ['multiOptions' => $empty + $this->agenda->getActivities($orgId)]);
        $this->metaModel->setIfExists('gap_id_procedure', ['multiOptions' => $empty + $this->agenda->getProcedures($orgId)]);
        $this->metaModel->setIfExists('gap_id_location', ['multiOptions' => $empty + $this->agenda->getLocations($orgId)]);

        if ($this->currentUser->hasPrivilege('pr.respondent.episodes-of-care.index')) {
            $this->metaModel->setIfExists('gap_id_episode', ['multiOptions' => $empty]);
            $this->metaModel->addDependency(['AppointmentCareEpisodeDependency', $this->agenda, $this->translatedUtil]);
        }
        return $this;
    }

    /**
     * The number of tokens changed by the last change
     *
     * @return int
     */
    public function getChangedTokenCount()
    {
        return $this->_changedTokenCount;
    }

    /**
     * Are linked tracks automatically updated?
     *
     * @return boolean
     */
    public function isAutoTrackUpdate()
    {
        return $this->autoTrackUpdate;
    }

    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        // When appointment id is not set, then check for existing instances of
        // this appointment using the source information
        if ((!isset($newValues['gap_id_appointment'])) &&
            isset($newValues['gap_id_in_source'], $newValues['gap_id_organization'], $newValues['gap_source'])) {

            $sql = "SELECT gap_id_appointment
                FROM gems__appointments
                WHERE gap_id_in_source = ? AND gap_id_organization = ? AND gap_source = ?";

            $id = $this->db->fetchOne(
                $sql,
                array($newValues['gap_id_in_source'], $newValues['gap_id_organization'], $newValues['gap_source'])
            );

            if ($id) {
                $newValues['gap_id_appointment'] = $id;
            }
        }

        $oldChanged = $this->getChanged();

        $returnValues = parent::save($newValues, $filter);

        if ($this->getChanged() && ($this->getChanged() !== $oldChanged)) {
            if ($this->isAutoTrackUpdate()) {
                $appointment = $this->agenda->getAppointment($returnValues);
                $event = $this->agenda->appointmentChanged($appointment);
                $this->_changedTokenCount += $event->getTokensChanged();
            }
        }
        // \MUtil\EchoOut\EchoOut::track($this->_changedTokenCount);

        return $returnValues;
    }

    /**
     * Automatically update linked tracks
     *
     * @param boolean $value
     * @return \Gems\Model\AppointmentModel (continuation pattern)
     */
    public function setAutoTrackUpdate($value = true)
    {
        $this->autoTrackUpdate = $value;

        return $this;
    }

    /**
     * Display the episode
     * @param int $episodeId
     * @return string
     */
    public function showEpisode(int|null $episodeId): HtmlElement|null
    {
        if (! $episodeId) {
            return null;
        }
        $episode = $this->agenda->getEpisodeOfCare($episodeId);

        if (! $episode->exists) {
            return $episodeId;
        }

        $url = $this->routeHelper->getRouteUrl('respondent.episodes-of-care.show', [
            \MUtil\Model::REQUEST_ID1 => $episode->getRespondent()->getPatientNumber(),
            \MUtil\Model::REQUEST_ID2 => $episode->getOrganizationId(),
            Model::EPISODE_ID => $episodeId,
        ]);

        return Html::create('a', $url, $episode->getDisplayString());
    }
}
