<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

use MUtil\Model;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @subpackage AppointmentAction
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentAction extends \Gems\Actions\RespondentChildActionAbstract
{
    /**
     *
     * @var \Gems\Tracker\Respondent
     */
    private $_respondent;

    /**
     * Appointment ID of current request (if any)
     *
     * Set by loadParams()
     *
     * @var int
     */
    protected $appointmentId;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = [
        'extraSort'   => ['gap_admission_time' => SORT_DESC],
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Agenda\\AppointmentsTableSnippet';

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Agenda\\AppointmentFormSnippet';

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $checkParameters = [
        'contentTitle' => 'getCheckTitle',
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $checkSnippets = [
        'Generic\\ContentTitleSnippet',
        'Agenda\\AppointmentShortSnippet',
        'Agenda\\AppointmentCheckSnippet',
        'Agenda\\ApplyFiltersInformation',
        ];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Agenda\\YesNoAppointmentDeleteSnippet';

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $indexParameters = [
        'contentTitle' => 'getContentTitle',
        ];

    /**
     * Organization ID of current request
     *
     * Set by loadParams()
     *
     * @var int
     */
    protected $organizationId;

    /**
     * Respondent ID of current request
     *
     * Set by loadParams()
     *
     * @var string
     */
    protected $respondentId;

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'Agenda\\AppointmentShowSnippet',
        'Track\\TracksForAppointment',
        'Agenda\\AppointmentTokensSnippet',
    ];

    /**
     * Perform checks on an Episode of care
     */
    public function checkAction()
    {
        if ($this->checkSnippets) {
            $params = $this->_processParameters($this->checkParameters);

            $this->addSnippets($this->checkSnippets, $params);
        }
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        // Load organizationId and respondentId
        $this->loadParams();

        $model = $this->loader->getModels()->createAppointmentModel();

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings($this->organizationId);

                if ($action == 'create') {
                    // Set default date to tomoorow.
                    $now  = new \MUtil\Date();
                    $now->addDay(1);

                    $loid = $this->db->fetchOne(
                            "SELECT gap_id_location
                                FROM gems__appointments
                                WHERE gap_id_user = ? AND gap_id_organization = ?
                                ORDER BY gap_admission_time DESC",
                            array($this->respondentId, $this->organizationId)
                            );

                    if ($loid !== false) {
                        $model->set('gap_id_location', 'default', $loid);
                    }

                    $model->set('gap_id_user',         'default', $this->respondentId);
                    $model->set('gap_manual_edit',     'default', 1);
                    $model->set('gap_admission_time',  'default', $now);
                } else {
                    // When there is something saved, then set manual edit to 1
                    $model->setSaveOnChange('gap_manual_edit');
                    $model->setOnSave(      'gap_manual_edit', 1);
                }
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
            $model->addFilter([
                'gap_id_user'         => $this->respondentId,
                'gap_id_organization' => $this->organizationId,
            ]);
        }

        return $model;
    }

    /**
     *
     * @return string
     */
    public function getCheckTitle()
    {
        return $this->_('Track field filter check for this appointment');
    }

    /**
     * Helper function to get the informed title for the index action.
     *
     * @return $string
     */
    public function getContentTitle()
    {
        $patientId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID1);
        if ($patientId) {
            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                return sprintf($this->_('Appointments for respondent number %s'), $patientId);
            }
            $orgId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID2);
            $respondent = $this->loader->getRespondent($patientId, $orgId);
            return sprintf($this->_('Appointments for respondent number %s: %s'), $patientId, $respondent->getName());
        }
        return $this->getIndexTitle();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Appointments');
    }

    /**
     * Get the respondent object
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        if (! $this->_respondent) {
            $id = $this->request->getAttribute(\Gems\Model::APPOINTMENT_ID);
            $patientNr = $this->request->getAttribute(\MUtil\Model::REQUEST_ID1);
            $orgId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID2);
            if ($id && ! ($patientNr || $orgId)) {
                $appointment = $this->loader->getAgenda()->getAppointment($id);
                $this->_respondent = $appointment->getRespondent();

                if (! $this->_respondent->exists) {
                    throw new \Gems\Exception($this->_('Unknown respondent.'));
                }

                $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
            } else {
                $this->_respondent = parent::getRespondent();
            }
        }

        return $this->_respondent;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('appointment', 'appointments', $count);
    }

    /**
     * Loads and checks the request parameters
     *
     * @throws \Gems\Exception
     */
    protected function loadParams()
    {
        $patientNr           = $this->request->getAttribute(Model::REQUEST_ID1);
        $this->appointmentId = $this->request->getAttribute(\Gems\Model::APPOINTMENT_ID);

        if ($this->appointmentId) {
            $select = $this->db->select();
            $select->from('gems__appointments', array('gap_id_user', 'gap_id_organization'))
                    ->joinInner(
                            'gems__respondent2org',
                            'gap_id_user = gr2o_id_user AND gap_id_organization = gr2o_id_organization',
                            array('gr2o_patient_nr')
                            )
                    ->where('gap_id_appointment = ?', $this->appointmentId);
            $data = $this->db->fetchRow($select);

            if ($data) {
                $this->organizationId = $data['gap_id_organization'];
                $this->respondentId   = $data['gap_id_user'];
                $patientNr            = $data['gr2o_patient_nr'];
            }
        } else {
            $this->organizationId = $this->request->getAttribute(Model::REQUEST_ID2);

            if ($patientNr && $this->organizationId) {
                $this->respondentId   = $this->util->getDbLookup()->getRespondentId(
                        $patientNr,
                        $this->organizationId
                        );
            }
        }

        if (! $this->respondentId) {
            throw new \Gems\Exception($this->_('Requested agenda data not available!'));
        } else {
            $orgs = $this->currentUser->getAllowedOrganizations();

            if (! isset($orgs[$this->organizationId])) {
                $org = $this->loader->getOrganization($this->organizationId);

                if ($org->exists()) {
                    throw new \Gems\Exception(
                            sprintf($this->_('You have no access to %s appointments!'), $org->getName())
                            );
                } else {
                    throw new \Gems\Exception($this->_('Organization does not exist.'));
                }
            }
        }
    }
}
