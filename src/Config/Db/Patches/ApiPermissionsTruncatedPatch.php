<?php

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\PatchAbstract;

class ApiPermissionsTruncatedTranslations extends PatchAbstract
{
    private array $translations = [
        'pr.api.api.patient.GET' => 'pr.api.fhir.patient.GET',
        'pr.api.api.patient.structure' => 'pr.api.fhir.patient.structure',
        'pr.api.api.appointment.GET' => 'pr.api.fhir.appointment.GET',
        'pr.api.api.appointment.structure' => 'pr.api.fhir.appointment.structure',
        'pr.api.api.episode-of-care.GET' => 'pr.api.fhir.episode-of-care.GET',
        'pr.api.api.episode-of-care.structure' => 'pr.api.fhir.episode-of-care.structure',
        'pr.api.api.location.GET' => 'pr.api.fhir.location.GET',
        'pr.api.api.location.structure' => 'pr.api.fhir.location.structure',
        'pr.api.api.organization.GET' => 'pr.api.fhir.organization.GET',
        'pr.api.api.organization.structure' => 'pr.api.fhir.organization.structure',
        'pr.api.api.practitioner.GET' => 'pr.api.fhir.practitioner.GET',
        'pr.api.api.practitioner.structure' => 'pr.api.fhir.practitioner.structure',
        'pr.api.api.related-person.GET' => 'pr.api.fhir.related-person.GET',
        'pr.api.api.related-person.structure' => 'pr.api.fhir.related-person.structure',
        'pr.api.api.questionnaire.GET' => 'pr.api.fhir.questionnaire.GET',
        'pr.api.api.questionnaire.structure' => 'pr.api.fhir.questionnaire.structure',
        'pr.api.api.questionnaire-task.GET' => 'pr.api.fhir.questionnaire-task.GET',
        'pr.api.api.questionnaire-task.PATCH' => 'pr.api.fhir.questionnaire-task.PATCH',
        'pr.api.api.questionnaire-task.structure' => 'pr.api.fhir.questionnaire-task.structure',
        'pr.api.api.questionnaire-response.GET' => 'pr.api.fhir.questionnaire-response.GET',
        'pr.api.api.questionnaire-response.structure' => 'pr.api.fhir.questionnaire-response.structure',
        'pr.api.api.care-plan.GET' => 'pr.api.fhir.care-plan.GET',
        'pr.api.api.care-plan.structure' => 'pr.api.fhir.care-plan.structure',
        'pr.api.api.codesystem/service-type.GET' => 'pr.api.api.codesystem/service-type.GET',
        'pr.api.api.codesystem/service-type.structure' => 'pr.api.fhir.codesystem/service-type.structure',
        'pr.api.api.consent.GET' => 'pr.api.fhir.consent.GET',
        'pr.api.api.consent.structure' => 'pr.api.fhir.consent.structure',

    ];

    public function getDescription(): string|null
    {
        return 'Translate api permissions with new untrucated names';
    }

    public function getOrder(): int
    {
        return 20260803170000;
    }

    public function up(): array
    {
        $patches = [];

        foreach ($this->translations as $oldName => $newName) {
            $patches[] = "UPDATE `gems__roles`
SET `grl_privileges` = REPLACE(`grl_privileges`, $oldName, $newName)
WHERE `grl_privileges` LIKE '%$oldName%';";
        }

        return $patches;
    }
}