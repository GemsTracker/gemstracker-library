<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model;

use MUtil\Model\Type\JsonData;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 16-May-2018 17:05:54
 */
class EpisodeOfCareModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Self constructor
     */
    public function __construct()
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('episodesofcare', 'gems__episodes_of_care', 'gec');

        $this->addTable(
                'gems__respondent2org',
                array('gec_id_user' => 'gr2o_id_user', 'gec_id_organization' => 'gr2o_id_organization'),
                'gr2o',
                false
                );

        $this->addColumn(new \Zend_Db_Expr("'careepisodes'"), \Gems_Model::ID_TYPE);
        $this->addColumn(
                new \Zend_Db_Expr(
                        "(SELECT COUNT(*) FROM gems__appointments WHERE gap_id_episode = gec_episode_of_care_id)"
                        ),
                'appointment_count');

        $this->setKeys(array(\Gems_Model::EPISODE_ID => 'gec_episode_of_care_id'));
    }

    /**
     * Add the join tables instead of lookup tables.
     */
    protected function _addJoinTables()
    {
        $this->addTable('gems__respondents', array('gec_id_user' => 'grs_id_user'));

        if ($this->has('gec_id_organization')) {
            $this->addTable(
                    'gems__organizations',
                    array('gec_id_organization' => 'gor_id_organization'),
                    'gor',
                    false
                    );
        }
        if ($this->has('gec_id_attended_by')) {
            $this->addLeftTable(
                    'gems__agenda_staff',
                    array('gec_id_attended_by' => 'gas_id_staff'),
                    'gas',
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
            $codes = $agenda->getStatusCodesInactive();

            $this->addColumn(
                    "CASE WHEN gec_status IN ('" .
                        implode("', '", array_keys($codes)) .
                        "') THEN 'deleted' ELSE '' END",
                    'row_class'
                    );

            if (isset($codes['C'])) {
                $cancelCode = 'C';
            } elseif ($codes) {
                reset($codes);
                $cancelCode = key($codes);
            } else {
                $cancelCode = null;
            }
            if ($cancelCode) {
                $this->setDeleteValues('gec_status', $cancelCode);
            }
        }
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Model_AppointmentModel
     */
    public function applyBrowseSettings()
    {
        $this->_addJoinTables();
        $this->resetOrder();

        $agenda = $this->loader->getAgenda();

        $this->setIfExists('gec_status',    'label', $this->_('Status'),
                'multiOptions', $agenda->getEpisodeStatusCodes());

        $this->setIfExists('gec_startdate', 'label', $this->_('Start date'),
                'dateFormat',  'dd-MM-yyyy',
                'description', $this->_('dd-mm-yyyy'));
        $this->setIfExists('gec_enddate', 'label', $this->_('End date'),
                'dateFormat',  'dd-MM-yyyy',
                'description', $this->_('dd-mm-yyyy'));

        $this->setIfExists('gec_subject',   'label', $this->_('Subject'));
        $this->setIfExists('gec_diagnosis', 'label', $this->_('Diagnosis'));

        $this->set('appointment_count',     'label', $this->_('Appointments'));

        $jsonType = new JsonData(10);
        $jsonType->apply($this, 'gec_diagnosis_data', false);
        $jsonType->apply($this, 'gec_extra_data',     false);

        $this->refreshGroupSettings();

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems_Model_AppointmentModel
     */
    public function applyDetailSettings()
    {
        $this->resetOrder();

        $agenda   = $this->loader->getAgenda();
        $dbLookup = $this->util->getDbLookup();
        $empty    = $this->util->getTranslated()->getEmptyDropdownArray();

        $this->setIfExists('gec_id_organization', 'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $empty + $dbLookup->getOrganizations());

        $this->setIfExists('gec_status',         'label', $this->_('Status'),
                'multiOptions', $agenda->getEpisodeStatusCodes());

        $this->setIfExists('gec_startdate',      'label', $this->_('Start date'),
                'dateFormat',  'dd-MM-yyyy',
                'description', $this->_('dd-mm-yyyy'));
        $this->setIfExists('gec_enddate',        'label', $this->_('End date'),
                'dateFormat',  'dd-MM-yyyy',
                'description', $this->_('dd-mm-yyyy'));

        $this->setIfExists('gec_id_attended_by', 'label', $this->_('With'),
                'multiOptions', $empty + $agenda->getHealthcareStaff());

        $this->setIfExists('gec_subject',        'label', $this->_('Subject'));
        $this->setIfExists('gec_comment',        'label', $this->_('Comment'));
        $this->setIfExists('gec_diagnosis',      'label', $this->_('Diagnosis'));
        $this->set('appointment_count',     'label', $this->_('Appointments'));

        $jsonType = new JsonData(10);
        $jsonType->apply($this, 'gec_diagnosis_data', true);
        $jsonType->apply($this, 'gec_extra_data',     true);

        if ($this->currentUser->hasPrivilege('pr.episodes.rawdata')) {
            $this->setIfExists('gec_diagnosis_data', 'label', $this->_('Diagnosis data'));
            $this->setIfExists('gec_extra_data',     'label', $this->_('Extra data'));
        }

        $this->refreshGroupSettings();

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param int $orgId The id of the current organization
     * @param int $respId The id of the current respondent
     * @return \Gems_Model_AppointmentModel
     */
    public function applyEditSettings($orgId, $respId)
    {
        $this->applyDetailSettings();

        $agenda = $this->loader->getAgenda();
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();

        $this->setIfExists('gec_id_user',         'default', $respId,
                'elementClass', 'Hidden');

        $this->setIfExists('gec_id_organization', 'default', $orgId,
                'elementClass', 'Hidden');

        $this->setIfExists('gec_status',          'required', true);
        $this->setIfExists('gec_startdate',       'default', new \MUtil_Date(),
                'elementClass', 'Date',
                'required', true);

        $this->setIfExists('gec_enddate',         'elementClass', 'Date');
        $this->setIfExists('gec_subject',         'required', true);
        $this->setIfExists('gec_comment',         'elementClass', 'Textarea',
                'rows', 5);
        $this->setIfExists('gec_diagnosis',       'required', true);
        $this->set('appointment_count',           'elementClass', 'Exhibitor');

        if ($this->currentUser->hasPrivilege('pr.episodes.rawdata')) {
            $this->setIfExists('gec_diagnosis_data', 'elementClass', 'Exhibitor');
            $this->setIfExists('gec_extra_data',     'elementClass', 'Exhibitor');
        }

        return $this;
    }

    /**
     * Function to re-apply all the masks and settings for the current group
     *
     * @return void
     */
    public function refreshGroupSettings()
    {
        $group = $this->currentUser->getGroup();
        if ($group instanceof Group) {
            $group->applyGroupToModel($this, false);
        }
    }
}
