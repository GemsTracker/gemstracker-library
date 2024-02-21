<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\FilterTracer;
use Gems\Model;
use Gems\Tracker\Engine\FieldsDefinition;
use Zalt\Html\Sequence;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 07-Jan-2020 11:49:55
 */
class AppointmentCheckSnippet extends AppointmentCheckSnippetAbstract
{
    /**
     *
     * @param \Zalt\Html\Sequence $seq
     */
    protected function appendFormInfo(Sequence $seq)
    {
        $seq->br();

        $options = $this->getModeOptions();

        $seq->div(sprintf(
                $this->_("'%s' shows what will be checked, clicking on '%s' will perform actual changes!'"),
                $options[0],
                $options[1]
                ), ['class' => 'alert alert-danger', 'role' => 'alert']);
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues(): array
    {
        return ['runmode' => 0];
    }

    public function getHtmlOutput()
    {
        $appId = $this->requestInfo->getParam(Model::APPOINTMENT_ID);
        if (! $appId) {
            return $this->_('No appointment specified');
        }
        $appointment = $this->agenda->getAppointment($appId);
        $form        = parent::getHtmlOutput();
        $seq         = $this->getHtmlSequence();
        $tracer      = new FilterTracer();

        $seq->append($form);

        $this->appendFormInfo($seq);

        $tracer->executeChanges = $this->formData['runmode'];

        $this->agenda->updateTracksForAppointment($appointment, $tracer);

        $seq->h1($this->_('Result'));
        $seq->h2($this->_('Existing tracks check'));
        $this->appendCheckedTracks($seq, $tracer);

        if ($tracer->getSkippedFilterCheck()) {
            $seq->h2($this->_('Creation by filter check skipped'));
            $this->appendFiltersSkipped($seq, $tracer, $appointment);
        } else {
            $seq->h2($this->_('Creation by filter check'));
            $this->appendFiltersChecked($seq, $tracer);
        }
        if ($tracer->executeChanges) {
            $this->logChanges(1);
        }

        return $seq;
    }
}
