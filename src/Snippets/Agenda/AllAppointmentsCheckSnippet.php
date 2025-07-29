<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\Appointment;
use Gems\Agenda\FilterTracer;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @since      Class available since version 1.0
 */
class AllAppointmentsCheckSnippet extends AppointmentCheckSnippetAbstract
{
    /**
     * @var Appointment[];
     */
    public array $appointments = [];

    /**
     * @inheritDoc
     */
    protected function addFormElements(mixed $form)
    {
    }

    public function getHtmlOutput()
    {
        $seq = $this->getHtmlSequence();

        $patientNr = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID1);
        $organizationId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID2);
        $apps = array_reverse($this->agenda->getActiveAppointments(
            null,
            intval($organizationId),
            $patientNr), true);

        $testOrgs = [];
        foreach ($apps as $app) {
            /**
             * @var Appointment $app
             */
            $testOrgs[$app->getOrganizationId()] = $app->getOrganizationId();
        }
        $this->currentUser->assertAccessToOrganizationId($testOrgs, null);

        if ($apps) {
            $table = $seq->table();
            $table->appendAttrib('class', 'check-appointments');
            $table->caption($this->_('Appointments checked'));

            $head = $table->thead();
            $head->th($this->_('Appointment'));
            $head->th($this->_('Existing tracks check'));
            $head->th($this->_('Creation by filter check'));

            $staff = $this->agenda->getHealthcareStaff();

            foreach ($apps as $appointmentId => $description) {
                $appointment = $this->agenda->getAppointment($appointmentId);
                $tracer      = new FilterTracer();

                $tracer->executeChanges = true; // $this->formData['runmode'];

                $row = $table->tbody()->tr();
                $row->td($appointment->getDisplayString());
                $this->agenda->updateTracksForAppointment($appointment, $tracer);

                $this->appendCheckedTracks($row->td()->seq(), $tracer);
                $tdSeq = $row->td()->seq();
                if ($tracer->getSkippedFilterCheck()) {
                    $tdSeq->strong($this->_('Check skipped'));
                    $this->appendFiltersSkipped($tdSeq, $tracer, $appointment);
                } else {
                    $tdSeq->strong($this->_('Check executed'));
                    $this->appendFiltersChecked($tdSeq, $tracer);
                }
                // if ($tracer->executeChanges) {
                    $this->logChanges(1);
                //}
            }
        } else {
            $seq->pInfo($this->_('This respondent has no appointments to check'));
        }

        return $seq;
    }


}