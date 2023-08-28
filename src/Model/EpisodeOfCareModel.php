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

use Gems\Agenda\Agenda;
use Gems\Util\Translated;
use MUtil\Model\Type\JsonData;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 16-May-2018 17:05:54
 */
class EpisodeOfCareModel extends MaskedModel
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Self constructor
     */
    public function __construct(protected Agenda $agenda)
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('episodesofcare', 'gems__episodes_of_care', 'gec');

        $this->addTable(
                'gems__respondent2org',
                array('gec_id_user' => 'gr2o_id_user', 'gec_id_organization' => 'gr2o_id_organization'),
                'gr2o',
                false
                );

        $this->addColumn(new \Zend_Db_Expr("'careepisodes'"), \Gems\Model::ID_TYPE);
        $this->addColumn(
                new \Zend_Db_Expr(
                        "(SELECT COUNT(*) FROM gems__appointments WHERE gap_id_episode = gec_episode_of_care_id)"
                        ),
                'appointment_count');

        $keys = $this->_getKeysFor('gems__respondent2org');
        $keys[\Gems\Model::EPISODE_ID] = 'gec_episode_of_care_id';
        $this->setKeys($keys);
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
        if ($this->agenda) {
            $codes = $this->agenda->getStatusCodesInactive();

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
     * @return \Gems\Model\AppointmentModel
     */
    public function applyBrowseSettings()
    {
        $this->_addJoinTables();
        $this->resetOrder();

        $this->setIfExists('gec_status',    'label', $this->_('Status'),
                'multiOptions', $this->agenda->getEpisodeStatusCodes());

        $this->setIfExists('gec_startdate', 'label', $this->_('Start date'),
                'description', $this->_('dd-mm-yyyy'));
        $this->setIfExists('gec_enddate', 'label', $this->_('End date'),
                'description', $this->_('dd-mm-yyyy'));

        $this->setIfExists('gec_subject',   'label', $this->_('Subject'));
        $this->setIfExists('gec_diagnosis', 'label', $this->_('Diagnosis'));

        $this->set('appointment_count',     'label', $this->_('Appointments'));

        $jsonType = new JsonData(10);
        $jsonType->apply($this, 'gec_diagnosis_data', false);
        $jsonType->apply($this, 'gec_extra_data',     false);

        $this->applyMask();

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\AppointmentModel
     */
    public function applyDetailSettings()
    {
        $this->resetOrder();

        $dbLookup = $this->util->getDbLookup();
        $empty    = $this->translatedUtil->getEmptyDropdownArray();

        $this->setIfExists('gec_id_organization', 'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $empty + $dbLookup->getOrganizations());

        $this->setIfExists('gec_status',         'label', $this->_('Status'),
                'multiOptions', $this->agenda->getEpisodeStatusCodes());

        $this->setIfExists('gec_startdate',      'label', $this->_('Start date'),
                'description', $this->_('dd-mm-yyyy'));
        $this->setIfExists('gec_enddate',        'label', $this->_('End date'),
                'description', $this->_('dd-mm-yyyy'));

        $this->setIfExists('gec_id_attended_by', 'label', $this->_('With'),
                'multiOptions', $empty + $this->agenda->getHealthcareStaff());

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

        $this->applyMask();

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param int $orgId The id of the current organization
     * @param int $respId The id of the current respondent
     * @return \Gems\Model\AppointmentModel
     */
    public function applyEditSettings($orgId, $respId)
    {
        $this->applyDetailSettings();

        $empty  = $this->translatedUtil->getEmptyDropdownArray();

        $this->setIfExists('gec_id_user',         'default', $respId,
                'elementClass', 'Hidden');

        $this->setIfExists('gec_id_organization', 'default', $orgId,
                'elementClass', 'Hidden');

        $this->setIfExists('gec_status',          'required', true);
        $this->setIfExists('gec_startdate',       'default', new \DateTimeImmutable(),
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
}
