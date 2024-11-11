<?php

namespace Gems\Snippets\Agenda;

use Gems\Model\AppointmentModel;
use Zalt\Model\Data\DataReaderInterface;

class CalendarDiagnosisExampleTableSnippet extends CalendarExampleTableSnippet
{
    protected function createModel(): DataReaderInterface
    {
        $model = parent::createModel();
        if ($model instanceof AppointmentModel) {
            $model->addTable('gems__agenda_diagnoses', ['gad_diagnosis_code' => 'gap_diagnosis_code']);
        }
        return $model;
    }
}