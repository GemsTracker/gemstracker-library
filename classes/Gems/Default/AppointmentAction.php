<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentAction.php$
 */

/**
 *
 *
 * @package    Default
 * @subpackage AppointmentAction
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_AppointmentAction extends Gems_Controller_ModelSnippetActionAbstract
{
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
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraSort'   => array('gap_admission_time' => SORT_ASC),
        );

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

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
    protected $showSnippets = array('Generic_ContentTitleSnippet', 'Agenda_AppointmentShowSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
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
                    $now  = new MUtil_Date();
                    $now->addDay(1);

                    $loid = $this->db->fetchOne(
                            "SELECT gap_id_location
                                FROM gems__appointments
                                WHERE gap_id_user = ? AND gap_id_organization = ?
                                ORDER BY gap_admission_time DESC",
                            array($this->respId, $this->orgId)
                            );

                    if ($loid !== false) {
                        $model->set('gap_id_location', 'default', $loid);
                    }

                    $model->set('gap_id_user',         'default', $this->respondentId);
                    $model->set('gap_admission_time',  'default', $now); //->toString('yyyy-MM-dd hh:mm:ss'));
                }
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
            $model->addFilter(array(
                'gap_id_user'         => $this->respondentId,
                'gap_id_organization' => $this->organizationId,
                ));
        }

        return $model;
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
     * @throws Gems_Exception
     */
    protected function loadParams()
    {
        $patientNr           = $this->_getParam(MUtil_Model::REQUEST_ID1);
        $this->appointmentId = $this->_getParam(Gems_Model::APPOINTMENT_ID);

        if ($this->appointmentId) {
            $select = $this->db->select();
            $select->from('gems__appointments', array('gap_id_user', 'gap_id_organization'))
                    ->where('gap_id_appointment = ?', $this->appointmentId);
            $data = $this->db->fetchRow($select);

            if ($data) {
                $this->organizationId = $data['gap_id_organization'];
                $this->respondentId   = $data['gap_id_user'];
            }
        } else {
            $this->organizationId = $this->_getParam(MUtil_Model::REQUEST_ID2);

            if ($patientNr && $this->organizationId) {
                $this->respondentId   = $this->util->getDbLookup()->getRespondentId(
                        $patientNr,
                        $this->organizationId
                        );
            }
        }

        if (! $this->respondentId) {
            throw new Gems_Exception($this->_('Requested agenda data not available!'));
        } else {
            $orgs = $this->loader->getCurrentUser()->getAllowedOrganizations();

            if (! isset($orgs[$this->organizationId])) {
                $org = $this->loader->getOrganization($this->organizationId);

                if ($org->exists()) {
                    throw new Gems_Exception(
                            sprintf($this->_('You have no access to %s appointments!'), $org->getName())
                            );
                } else {
                    throw new Gems_Exception($this->_('Organization does not exist.'));
                }
            }
        }

        $source = $this->menu->getParameterSource();
        if ($this->appointmentId) {
            $source->setAppointmentId($this->appointmentId);
        }
        if ($patientNr && $this->organizationId) {
            $source->setPatient($patientNr, $this->organizationId);
        }
    }
}
