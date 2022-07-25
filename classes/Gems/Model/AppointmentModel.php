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

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentModel extends \Gems\Model\JoinModel
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
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Self constructor
     */
    public function __construct()
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('appointments', 'gems__appointments', 'gap');

        $this->addTable(
                'gems__respondent2org',
                array('gap_id_user' => 'gr2o_id_user', 'gap_id_organization' => 'gr2o_id_organization'),
                'gr2o',
                false
                );

        $this->addColumn(new \Zend_Db_Expr("'appointment'"), \Gems\Model::ID_TYPE);
        $this->setKeys(array(\Gems\Model::APPOINTMENT_ID => 'gap_id_appointment'));
    }

    /**
     * Add the join tables instead of lookup tables.
     */
    protected function _addJoinTables()
    {
        $this->addTable('gems__respondents', array('gap_id_user' => 'grs_id_user'));

        if ($this->has('gap_id_organization')) {
            $this->addTable(
                    'gems__organizations',
                    array('gap_id_organization' => 'gor_id_organization'),
                    'gor',
                    false
                    );
        }
        if ($this->has('gap_id_attended_by')) {
            $this->addLeftTable(
                    'gems__agenda_staff',
                    array('gap_id_attended_by' => 'gas_id_staff'),
                    'gas',
                    false
                    );
        }
        /*
        if ($this->has('gap_id_referred_by')) {
            $this->addLeftTable(
                    array('ref_staff' => 'gems__agenda_staff'),
                    array('gap_id_referred_by' => 'ref_staff.gas_id_staff')
                    );
        } // */
        if ($this->has('gap_id_activity')) {
            $this->addLeftTable(
                    'gems__agenda_activities',
                    array('gap_id_activity' => 'gaa_id_activity'),
                    'gap',
                    false
                    );
        }
        if ($this->has('gap_id_procedure')) {
            $this->addLeftTable(
                    'gems__agenda_procedures',
                    array('gap_id_procedure' => 'gapr_id_procedure'),
                    'gapr',
                    false
                    );
        }
        if ($this->has('gap_id_location')) {
            $this->addLeftTable(
                    'gems__locations',
                    array('gap_id_location' => 'glo_id_location'),
                    'glo',
                    false
                    );
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        $agenda = $this->loader->getAgenda();

        if ($agenda) {
            $this->addColumn(
                    "CASE WHEN gap_status IN ('" .
                        implode("', '", $agenda->getStatusKeysInactive()) .
                        "') THEN 'deleted' ELSE '' END",
                    'row_class'
                    );

            $codes = $agenda->getStatusCodesInactive();
            if (isset($codes['CA'])) {
                $cancelCode = 'CA';
            } elseif ($codes) {
                reset($codes);
                $cancelCode = key($codes);
            } else {
                $cancelCode = null;
            }
            if ($cancelCode) {
                $this->setDeleteValues('gap_status', $cancelCode);
            }
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
        $this->resetOrder();

        $agenda     = $this->loader->getAgenda();

        $this->setIfExists('gap_admission_time',     'label', $this->_('Appointment'),
                // 'formatFunction', array($this->util->getTranslated(), 'describeTimeFromNow'),
                'dateFormat',  'dd-MM-yyyy HH:mm',
                'description', $this->_('dd-mm-yyyy hh:mm'));
        $this->setIfExists('gap_status',             'label', $this->_('Type'),
                'multiOptions', $agenda->getStatusCodes());

        if ($this->currentUser->hasPrivilege('pr.episodes')) {
            $this->setIfExists('gap_id_episode',        'label', $this->_('Episode'),
                    'formatFunction', [$this, 'showEpisode']);
        }

        $this->setIfExists('gas_name',              'label', $this->_('With'));
        //  $this->setIfExists('ref_staff.gas_name',    'label', $this->_('By'));
        $this->setIfExists('gaa_name',              'label', $this->_('Activities'));
        $this->setIfExists('gapr_name',             'label', $this->_('Procedures'));
        $this->setIfExists('glo_name',              'label', $this->_('Location'));
        $this->setIfExists('gor_name',              'label', $this->_('Organization'));
        $this->setIfExists('gap_subject',           'label', $this->_('Subject'));

        $dels = $this->loader->getAgenda()->getStatusKeysInactiveDbQuoted();
        if ($dels) {
            $this->addColumn(
                    new \Zend_Db_Expr("CASE WHEN gap_status IN ($dels) THEN 'deleted' ELSE '' END "),
                    'row_class'
                    );
        }

        $this->refreshGroupSettings();

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
        $this->resetOrder();

        $agenda     = $this->loader->getAgenda();
        $dbLookup   = $this->util->getDbLookup();
        $empty      = $this->util->getTranslated()->getEmptyDropdownArray();

        $this->setIfExists('gap_admission_time',  'label', $this->_('Appointment'),
                'dateFormat',  'dd-MM-yyyy HH:mm',
                'description', $this->_('dd-mm-yyyy hh:mm'));
        $this->setIfExists('gap_discharge_time',  'label', $this->_('Discharge'),
                'dateFormat',  'dd-MM-yyyy HH:mm',
                'description', $this->_('dd-mm-yyyy hh:mm'));
        $this->setIfExists('gap_code',            'label', $this->_('Type'),
                'multiOptions', $agenda->getTypeCodes());
        $this->setIfExists('gap_status',          'label', $this->_('Status'),
                'multiOptions', $agenda->getStatusCodes());
        if ($this->currentUser->hasPrivilege('pr.episodes')) {
            $this->setIfExists('gap_id_episode',        'label', $this->_('Episode'),
                    'formatFunction', [$this, 'showEpisode'],
                    'required', false);
        }

        $this->setIfExists('gap_id_attended_by',  'label', $this->_('With'),
                'multiOptions', $empty + $agenda->getHealthcareStaff());
        $this->setIfExists('gap_id_referred_by',  'label', $this->_('Referrer'),
                'multiOptions', $empty + $agenda->getHealthcareStaff());
        $this->setIfExists('gap_id_activity',     'label', $this->_('Activities'));
        $this->setIfExists('gap_id_procedure',    'label', $this->_('Procedures'));
        $this->setIfExists('gap_id_location',     'label', $this->_('Location'));
        $this->setIfExists('gap_id_organization', 'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $empty + $dbLookup->getOrganizations());
        $this->setIfExists('gap_subject',         'label', $this->_('Subject'));
        $this->setIfExists('gap_comment',         'label', $this->_('Comment'));

        if ($setMulti) {
            $this->setIfExists('gap_id_activity',     'multiOptions', $empty + $agenda->getActivities());
            $this->setIfExists('gap_id_procedure',    'multiOptions', $empty + $agenda->getProcedures());
            $this->setIfExists('gap_id_location',     'multiOptions', $empty + $agenda->getLocations());
        }

        $this->refreshGroupSettings();

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

        $agenda = $this->loader->getAgenda();
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();

        $this->setIfExists('gap_id_organization', 'default', $orgId ?: $this->currentOrganization->getId());
        $this->setIfExists('gap_admission_time',  'elementClass', 'Date');
        $this->setIfExists('gap_discharge_time',  'elementClass', 'Date');
        $this->setIfExists('gap_status',          'required', true);
        $this->setIfExists('gap_comment',         'elementClass', 'Textarea', 'rows', 5);

        $this->setIfExists('gap_id_activity',     'multiOptions', $empty + $agenda->getActivities($orgId));
        $this->setIfExists('gap_id_procedure',    'multiOptions', $empty + $agenda->getProcedures($orgId));
        $this->setIfExists('gap_id_location',     'multiOptions', $empty + $agenda->getLocations($orgId));

        if ($this->currentUser->hasPrivilege('pr.episodes')) {
            $this->setIfExists('gap_id_episode', 'multiOptions', $empty);
            $this->addDependency('AppointmentCareEpisodeDependency');
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

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null)
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
                $appointment = $this->loader->getAgenda()->getAppointment($returnValues);

                $this->_changedTokenCount += $appointment->updateTracks();
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
    public function showEpisode($episodeId)
    {
        if (! $episodeId) {
            return null;
        }
        $episode = $this->loader->getAgenda()->getEpisodeOfCare($episodeId);

        if (! $episode->exists) {
            return $episodeId;
        }

        $episodeItem = $this->menu->findAllowedController('care-episode', 'show');
        if ($episodeItem) {
            $href = $episodeItem->toHRefAttribute(['gec_episode_of_care_id' => $episodeId]);
        } else {
            $href = false;
        }

        if (! $href) {
            return $episode->getDisplayString();
        }

        $onclick = new \MUtil\Html\OnClickArrayAttribute();
        $onclick->addCancelBubble(true);
        return \MUtil\Html::create('a', $href, $episode->getDisplayString(), $onclick);
    }

    /**
     * Function to re-apply all the masks and settings for the current group
     *
     * @return void
     */
    public function refreshGroupSettings()
    {
        $group = $this->currentUser->getGroup();
        if ($group instanceof \Gems\User\Group) {
            $group->applyGroupToModel($this, false);
        }
    }
}
