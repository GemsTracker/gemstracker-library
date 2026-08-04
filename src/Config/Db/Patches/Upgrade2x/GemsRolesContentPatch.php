<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config\Db\Patches\Upgrade2x
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config\Db\Patches\Upgrade2x;

/**
 * @package    Gems
 * @subpackage Config\Db\Patches\Upgrade2x
 * @since      Class available since version 1.0
 */
class GemsRolesContentPatch extends \Gems\Db\Migration\PatchAbstract
{
    /**
     * Is run after all other changes
     * @var array|string[] role that has to exist => role them assigned
     */
    protected array $extraRoles = [
        'pr.track-builder.track-maintenance.create' => 'pr.track-builder.track-maintenance.track-fields.create',
        'pr.track-builder.track-maintenance.delete' => 'pr.track-builder.track-maintenance.track-fields.delete',
        'pr.track-builder.track-maintenance.edit' => 'pr.track-builder.track-maintenance.track-fields.edit',
        'pr.track-builder.track-maintenance.index' => 'pr.track-builder.track-maintenance.track-fields.index',
        'pr.track-builder.track-maintenance.show' => 'pr.track-builder.track-maintenance.track-fields.show',
        'pr.track-builder.track-maintenance.track-fields.create' => 'pr.track-builder.track-maintenance.track-rounds.create',
        'pr.track-builder.track-maintenance.track-fields.delete' => 'pr.track-builder.track-maintenance.track-rounds.delete',
        'pr.track-builder.track-maintenance.track-fields.edit' => 'pr.track-builder.track-maintenance.track-rounds.edit',
        'pr.track-builder.track-maintenance.track-fields.index' => 'pr.track-builder.track-maintenance.track-rounds.index',
        'pr.track-builder.track-maintenance.track-fields.show' => 'pr.track-builder.track-maintenance.track-rounds.show',
        'pr.track-builder.track-maintenance.check-track' => 'pr.track-builder.track-maintenance.recalc-fields',
        'pr.track-builder.track-maintenance.check-all' => 'pr.track-builder.track-maintenance.recalc-all-fields',
//        '' => '',
//        '' => '',
    ];

    /**
     * @var array|string[] full name old role => full name new role replacements
     */
    protected array $fullRoleNameChanges = [
        'pr.agenda-activity.create' => 'pr.setup.agenda.activity.create',
        'pr.agenda-activity.cleanup' => 'pr.setup.agenda.activity.cleanup',
        'pr.agenda-activity.delete' => 'pr.setup.agenda.activity.delete',
        'pr.agenda-activity.edit' => 'pr.setup.agenda.activity.edit',
        'pr.agenda-activity' => 'pr.setup.agenda.activity.index,pr.setup.agenda.activity.show',
        'pr.agenda-diagnosis.create' => 'pr.setup.agenda.diagnosis.create',
        'pr.agenda-diagnosis.cleanup' => 'pr.setup.agenda.diagnosis.cleanup',
        'pr.agenda-diagnosis.delete' => 'pr.setup.agenda.diagnosis.delete',
        'pr.agenda-diagnosis.edit' => 'pr.setup.agenda.diagnosis.edit',
        'pr.agenda-diagnosis' => 'pr.setup.agenda.diagnosis.index,pr.setup.agenda.diagnosis.show',
        'pr.agenda-filters' => 'pr.setup.agenda.filter.index,pr.setup.agenda.filter.show',
        'pr.agenda-filters.create' => 'pr.setup.agenda.filter.create',
        'pr.agenda-filters.delete' => 'pr.setup.agenda.filter.delete',
        'pr.agenda-filters.edit' => 'pr.setup.agenda.filter.edit',
        'pr.agenda-procedure.create' => 'pr.setup.agenda.procedure.create',
        'pr.agenda-procedure.cleanup' => 'pr.setup.agenda.procedure.cleanup',
        'pr.agenda-procedure.delete' => 'pr.setup.agenda.procedure.delete',
        'pr.agenda-procedure.edit' => 'pr.setup.agenda.procedure.edit',
        'pr.agenda-procedure' => 'pr.setup.agenda.procedure.index,pr.setup.agenda.procedure.show',
        'pr.agenda-staff.create' => 'pr.setup.agenda.staff.create',
        'pr.agenda-staff.cleanup' => 'pr.setup.agenda.staff.cleanup',
        'pr.agenda-staff.delete' => 'pr.setup.agenda.staff.delete',
        'pr.agenda-staff.edit' => 'pr.setup.agenda.staff.edit',
        'pr.agenda-staff' => 'pr.setup.agenda.staff.index,pr.setup.agenda.staff.show',
        'pr.appointments.create' => 'pr.respondent.appointments.create',
        'pr.appointments.check' => 'pr.respondent.appointments.check-all,pr.respondent.appointments.check',
        'pr.appointments.delete' => 'pr.respondent.appointments.delete',
        'pr.appointments.edit' => 'pr.respondent.appointments.edit',
        'pr.appointments' => 'pr.respondent.appointments.index,pr.respondent.appointments.show',
        'pr.calendar' => 'pr.calendar.index',
        'pr.calendar.export' => 'pr.calendar.export',
        'pr.chartsetup.create' => 'pr.track-builder.chartconfig.create',
        'pr.chartsetup.delete' => 'pr.track-builder.chartconfig.delete',
        'pr.chartsetup.edit' => 'pr.track-builder.chartconfig.edit',
        'pr.chartsetup' => 'pr.track-builder.chartconfig.index,pr.track-builder.chartconfig.show',
        'pr.comm.job.export' => 'pr.setup.communication.job.export',
        'pr.comm.job.create' => 'pr.setup.communication.job.create',
        'pr.comm.job.delete' => 'pr.setup.communication.job.delete',
        'pr.comm.job.edit' => 'pr.setup.communication.job.edit',
        'pr.comm.job' => 'pr.setup.communication.job.index,pr.setup.communication.job.monitor,pr.setup.communication.job.show,pr.setup.communication.job.lock',
        'pr.comm.messenger.create' => 'pr.setup.communication.messenger.create',
        'pr.comm.messenger.delete' => 'pr.setup.communication.messenger.delete',
        'pr.comm.messenger.edit' => 'pr.setup.communication.messenger.edit',
        'pr.comm.messenger' => 'pr.setup.communication.messenger.index,pr.setup.communication.messenger.show',
        'pr.comm.template.create' => 'pr.setup.communication.template.create',
        'pr.comm.template.delete' => 'pr.setup.communication.template.delete',
        'pr.comm.template.edit' => 'pr.setup.communication.template.edit',
        'pr.comm.template' => 'pr.setup.communication.template.index,pr.setup.communication.template.show',
        'pr.conditions.create' => 'pr.track-builder.condition.create',
        'pr.conditions.delete' => 'pr.track-builder.condition.delete',
        'pr.conditions.edit' => 'pr.track-builder.condition.edit',
        'pr.conditions' => 'pr.track-builder.condition.index,pr.track-builder.condition.show',
        'pr.consent.export' => 'pr.setup.codes.consent.export',
        'pr.consent.create' => 'pr.setup.codes.consent.create',
        'pr.consent.delete' => 'pr.setup.codes.consent.delete',
        'pr.consent.edit' => 'pr.setup.codes.consent.edit',
        'pr.consent' => 'pr.setup.codes.consent.index,pr.setup.codes.consent.show',
        'pr.cron.job' => 'pr.setup.communication.job.execute-all,pr.setup.communication.job.execute',
        'pr.embed.login' => 'pr.embed.login',
        'pr.episodes.create' => 'pr.respondent.episodes-of-care.create',
        'pr.episodes.delete' => 'pr.respondent.episodes-of-care.delete',
        'pr.episodes.edit' => 'pr.respondent.episodes-of-care.edit',
        'pr.episodes.rawdata' => 'pr.episodes.rawdata',
        'pr.episodes' => 'pr.respondent.episodes-of-care.index,pr.respondent.episodes-of-care.show',
        'pr.export' => 'pr.export.downloads.index,pr.export.downloads.show,pr.export.downloads.delete',
        'pr.export.add-resp-nr' => 'pr.export.add-resp-nr',
        'pr.export.code-book-export' => 'pr.export.survey.index',
        'pr.export.gender-age' => 'pr.export.gender-age',
        'pr.group.export' => 'pr.setup.access.groups.download,pr.setup.access.mask.export',
        'pr.group.create' => 'pr.setup.access.groups.create,pr.setup.access.mask.create',
        'pr.group.delete' => 'pr.setup.access.groups.delete,pr.setup.access.mask.delete',
        'pr.group.edit' => 'pr.setup.access.groups.edit,pr.setup.access.mask.edit',
        'pr.group.switch' => 'pr.group.switch',
        'pr.group' => 'pr.setup.access.groups.index,pr.setup.access.groups.show,pr.setup.access.groups.diff,pr.setup.access.mask.index,pr.setup.access.mask.show',
        'pr.locations.create' => 'pr.setup.agenda.location.create',
        'pr.locations.cleanup' => 'pr.setup.agenda.location.cleanup,pr.setup.agenda.staff.merge',
        'pr.locations.delete' => 'pr.setup.agenda.location.delete',
        'pr.locations.edit' => 'pr.setup.agenda.location.edit',
        'pr.locations' => 'pr.setup.agenda.location.index,pr.setup.agenda.location.show',
        'pr.log' => 'pr.setup.log.activity.index,pr.setup.log.activity.show',
        'pr.log.files.download' => 'pr.setup.logfiles.download',
        'pr.log.files' => 'pr.setup.logfiles.index.pr.setup.logfiles.show',
        'pr.log.maintenance.edit' => 'pr.setup.log.maintenance.edit',
        'pr.log.maintenance' => 'pr.setup.log.maintenance.index,pr.setup.log.maintenance.show,pr.setup.log.maintenance.diff',
        'pr.mail.log' => 'pr.setup.communication.log.index,pr.setup.communication.log.show',
        'pr.mail.server.create' => 'pr.setup.communication.server.create',
        'pr.mail.server.delete' => 'pr.setup.communication.server.delete',
        'pr.mail.server.edit' => 'pr.setup.communication.server.edit',
        'pr.mail.server' => 'pr.setup.communication.server.index,pr.setup.communication.server.show',
        'pr.mailcode.export' => 'pr.setup.codes.mail-code.export',
        'pr.mailcode.create' => 'pr.setup.codes.mail-code.create',
        'pr.mailcode.delete' => 'pr.setup.codes.mail-code.delete',
        'pr.mailcode.edit' => 'pr.setup.codes.mail-code.edit',
        'pr.mailcode' => 'pr.setup.codes.mail-code.index,pr.setup.codes.mail-code.show',
        'pr.maintenance.clean-cache' => 'pr.setup.project-information.cacheclean,pr.setup.project-information.configcacheclean',
        'pr.maintenance.maintenance-mode' => 'pr.maintenance.maintenance-mode',
        'pr.option.edit' => 'pr.option.edit',
        'pr.option.2factor' => 'pr.option.two-factor',
        'pr.option.password' => 'pr.option.edit-auth',
        'pr.organization.check-all' => 'pr.setup.access.organizations.check-all',
        'pr.organization.create' => 'pr.setup.access.organizations.create',
        'pr.organization.check-org' => 'pr.setup.access.organizations.check-org',
        'pr.organization.delete' => 'pr.setup.access.organizations.delete',
        'pr.organization.edit' => 'pr.setup.access.organizations.edit',
        'pr.organization-switch' => 'pr.organization-switch',
        'pr.organization' => 'pr.setup.access.organizations.index,pr.setup.access.organizations.show',
        'pr.plan.compliance' => 'pr.overview.compliance.index',
        'pr.plan.compliance.export' => 'pr.overview.compliance.export',
        'pr.plan.consent.export' => 'pr.overview.consent-plan.export',
        'pr.plan.consent' => 'pr.overview.consent-plan.index,pr.overview.consent-plan.show',
        'pr.plan.fields.export' => 'pr.overview.field-overview.export,pr.overview.field-report.export',
        'pr.plan.fields' => 'pr.overview.field-overview.index,pr.overview.field-report.index',
        'pr.plan.mail-as-application' => 'pr.plan.mail-as-application',
        'pr.plan.overview' => 'pr.overview.overview-plan.index',
        'pr.plan.overview.export' => 'pr.overview.overview-plan.export',
        'pr.plan.respondent' => 'pr.overview.respondent-plan.index',
        'pr.plan.respondent.export' => 'pr.overview.respondent-plan.export',
        'pr.plan.summary' => 'pr.overview.summary.index',
        'pr.plan.summary.export' => 'pr.overview.summary.export',
        'pr.plan.token' => 'pr.overview.token-plan.index',
        'pr.plan.token.export' => 'pr.overview.token-plan.export',
        'pr.project' => 'pr.project.tracks.index,pr.project.tracks.show,pr.project.surveys.index,pr.project.surveys.show',
        'pr.project.questions' => 'pr.respondent.tracks.token.questions',
        'pr.project-information' => 'pr.setup.project-information.index,pr.setup.project-information.monitor,pr.setup.project-information.errors,pr.setup.project-information.php,pr.setup.project-information.php-errors,pr.setup.project-information.project,pr.setup.project-information.session,pr.setup.queue.messageCount.index',
        'pr.reception.create' => 'pr.setup.codes.reception.create',
        'pr.reception.delete' => 'pr.setup.codes.reception.delete',
        'pr.reception.edit' => 'pr.setup.codes.reception.edit',
        'pr.reception' => 'pr.setup.codes.reception.index,pr.setup.codes.reception.show',
        'pr.respondent.change-consent' => 'pr.respondent.change-consent',
        'pr.respondent.change-org' => 'pr.respondent.change-organization',
        'pr.respondent-commlog' => 'pr.respondent.communication-log.index,pr.respondent.communication-log.show',
        'pr.respondent.delete' => 'pr.respondent.delete',
        'pr.respondent.edit' => 'pr.respondent.edit',
        'pr.respondent.export-html' => 'pr.respondent.export-archive',
        'pr.respondent.export' => 'pr.respondent.export',
        'pr.respondent.import' => 'pr.respondent.import',
        'pr.respondent.create' => 'pr.respondent.create',
        'pr.respondent.multiorg' => 'pr.respondent.multiorg',
        'pr.respondent.undelete' => 'pr.respondent.delete',
        'pr.respondent.relation' => 'pr.respondent.relations.index,pr.respondent.relations.show',
        'pr.respondent.result' => 'pr.respondent.result',
        'pr.respondent' => 'pr.respondent.index,pr.respondent.show,pr.respondent.overview',
        'pr.respondent.relation.create' => 'pr.respondent.relations.create',
        'pr.respondent.relation.delete' => 'pr.respondent.relations.delete',
        'pr.respondent.relation.edit' => 'pr.respondent.relations.edit',
        'pr.respondent.select-on-track' => 'pr.respondent.select-on-track',
        'pr.respondent.show-deleted' => 'pr.respondent.show-deleted',
        'pr.respondent.who' => 'pr.respondent.who',
        'pr.respondent-log' => 'pr.setup.log.activity.index,pr.setup.log.activity.show',
        'pr.role.export' => 'pr.setup.access.roles.download',
        'pr.role.create' => 'pr.setup.access.roles.create',
        'pr.role.delete' => 'pr.setup.access.roles.delete',
        'pr.role.edit' => 'pr.setup.access.roles.edit',
        'pr.role' => 'pr.setup.access.roles.index,pr.setup.access.roles.overview,pr.setup.access.roles.privilege,pr.setup.access.roles.show,pr.setup.access.roles.diff',
        'pr.source.check-attributes-all' => 'pr.track-builder.source.attributes-all',
        'pr.source.check-answers-all' => 'pr.track-builder.source.check-all',
        'pr.source.create' => 'pr.track-builder.source.create',
        'pr.source.synchronize-all' => 'pr.track-builder.source.synchronize-all',
        'pr.source.check-attributes' => 'pr.track-builder.source.attributes',
        'pr.source.check-answers' => 'pr.track-builder.source.check',
        'pr.source.synchronize' => 'pr.track-builder.source.synchronize',
        'pr.source.delete' => 'pr.track-builder.source.delete',
        'pr.source.edit' => 'pr.track-builder.source.edit',
        'pr.source' => 'pr.track-builder.source.index,pr.track-builder.source.show',
        'pr.staff.import' => 'pr.setup.access.staff.import',
        'pr.staff.create' => 'pr.setup.access.staff.create',
        'pr.staff-log' => 'pr.setup.access.staff.log.index,',
        'pr.staff.deactivate' => 'pr.setup.access.staff.active-toggle',
        'pr.staff.reactivate' => 'pr.setup.access.staff.active-toggle',
        'pr.staff.edit' => 'pr.setup.access.staff.reset,pr.setup.access.staff.edit',
        'pr.staff.edit.all' => 'pr.staff.edit.all',
        'pr.staff.see.all' => 'pr.staff.see.all',
        'pr.staff' => 'pr.setup.access.staff.index,pr.setup.access.staff.show',
        'pr.survey' => 'pr.respondent.tokens.index,pr.respondent.tokens.show',
        'pr.survey-maintenance.check-all' => 'pr.track-builder.survey-maintenance.check-all',
        'pr.survey-maintenance.export' => 'pr.track-builder.survey-maintenance.export-settings',
        'pr.survey-maintenance.check' => 'pr.track-builder.survey-maintenance.attributes,pr.track-builder.survey-maintenance.check',
        'pr.survey-maintenance.code-book-export' => 'pr.track-builder.survey-maintenance.export-codebook.export',
        'pr.survey-maintenance.answer-import' => 'pr.track-builder.survey-maintenance.answer-imports,pr.track-builder.survey-maintenance.answer-importpr.track-builder.survey-maintenance.update-survey.run',
        'pr.survey-maintenance.edit' => 'pr.track-builder.survey-maintenance.edit',
        'pr.survey-maintenance' => 'pr.track-builder.survey-maintenance.index,pr.track-builder.survey-maintenance.show',
        'pr.systemuser.create' => 'pr.setup.access.system-user.create',
        'pr.systemuser.deactivate' => 'pr.setup.access.system-user.active-toggle',
        'pr.systemuser.reactivate' => 'pr.setup.access.system-user.active-toggle',
        'pr.systemuser.edit' => 'pr.setup.access.system-user.edit',
        'pr.systemuser.seepwd' => 'pr.systemuser.seepwd',
        'pr.systemuser' => 'pr.setup.access.system-user.index,pr.setup.access.system-user.show',
        'pr.token.mail.freetext' => 'pr.token.mail.freetext',    'pr.token' => 'pr.respondent.tracks.token.show',
        'pr.token.answers' => 'pr.respondent.tracks.token.answer',
        'pr.token.correct' => 'pr.respondent.tracks.token.correct',
        'pr.token.mail' => 'pr.respondent.tracks.token.email',
        'pr.token.undelete' => 'Patiënten-&gt;Toon-&gt;Trajecten-&gt;Toon traject-&gt;Kenmerk-&gt;Herstel!',
        'pr.token.check' => 'pr.respondent.tracks.token.check-token-answers,pr.respondent.tracks.token.check-token',
        'pr.token.edit' => 'pr.respondent.tracks.token.edit',
        'pr.token.print' => 'Patiënten-&gt;Toon-&gt;Trajecten-&gt;Toon traject-&gt;Kenmerk-&gt;Print PDF',
        'pr.track.answers' => 'pr.respondent.tracks.check-track-answers',
        'pr.track.check' => 'pr.respondent.tracks.check-track',
        'pr.track.insert' => 'pr.respondent.tracks.survey.insert',
        'pr.track.undelete' => 'pr.respondent.tracks.undelete',
        'pr.track.delete' => 'pr.respondent.tracks.delete',
        'pr.track.edit' => 'pr.respondent.tracks.edit',
        'pr.track.create' => 'pr.respondent.tracks.create',
        'pr.track' => 'pr.respondent.tracks.index,pr.respondent.tracks.show',
        'pr.track-maintenance.check-all' => 'pr.track-builder.track-maintenance.check-all,pr.track-builder.track-maintenance.recalc-all-fields',
        'pr.track-maintenance.create' => 'pr.track-builder.track-maintenance.create,pr.track-builder.track-maintenance.track-rounds.create,pr.track-builder.track-maintenance.track-fields.create',
        'pr.track-maintenance.check' => 'pr.track-builder.track-maintenance.check-track,pr.track-builder.track-maintenance.recalc-fields',
        'pr.track-maintenance.export' => 'pr.track-builder.track-maintenance.export',
        'pr.track-maintenance.delete' => 'pr.track-builder.track-maintenance.track-rounds.delete,pr.track-builder.track-maintenance.track-fields.delete,pr.track-builder.track-maintenance.delete',
        'pr.track-maintenance.trackperorg' => 'pr.track-builder.track-maintenance.track-overview.index',
        'pr.track-maintenance' => 'pr.track-builder.track-maintenance.index,pr.track-builder.track-maintenance.show,pr.track-builder.track-maintenance.track-rounds.index,pr.track-builder.track-maintenance.track-rounds.show,pr.track-builder.track-maintenance.track-fields.index,pr.track-builder.track-maintenance.track-fields.show',
        'pr.track-maintenance.edit' => 'pr.track-builder.track-maintenance.track-rounds.edit,pr.track-builder.track-maintenance.track-fields.edit,pr.track-builder.track-maintenance.edit',
        ];

    protected array $newPrivileges = [
        'guest' => 'pr.api.api.care-plan.GET,pr.api.api.consent.GET,pr.api.api.insertable-questionnaire.GET,pr.api.api.patient.GET,pr.api.api.questionnaire.GET,pr.api.api.questionnaire-response.GET,
            pr.api.api.questionnaire-task.GET,pr.api.api.questionnaire-task.PATCH,pr.api.api.related-person.GET,pr.api.api.tracks.GET,pr.api.insertable-questionnaire.structure,pr.api.ping,
            pr.api.questionnaire-response.structure,pr.api.questionnaire-task.structure,pr.api.questionnaire.structure,pr.api.related-person.structure,pr.respondent.overview,pr.respondent.tokens.create,
            pr.respondent.tracks.survey.view-survey,pr.respondent.tracks.token.answered-on-paper,pr.respondent.tracks.view',
        'staff' => 'pr.api.api.appointment.GET,pr.api.api.codesystem/service-type.GET,pr.api.api.comm-template.GET,pr.api.api.episode-of-care.GET,pr.api.api.location.GET,pr.api.api.organization.GET,
            pr.api.api.practitioner.GET,pr.api.api.respondent/email-token.GET,pr.api.api.respondent/email-token.PATCH,pr.api.appointment.structure,pr.api.care-plan.structure,
            pr.api.codesystem/service-type.structure,pr.api.tracks.structure,pr.api.consent.structure,pr.api.episode-of-care.structure,pr.api.location.structure,pr.api.organization.structure,
            pr.api.other-patient-numbers,pr.api.patient.structure,pr.api.practitioner.structure,pr.api.respondent/email-token.structure,pr.api.single-language-comm-template.structure,
            pr.respondent.tracks.token.delete,pr.respondent.tracks.token.undelete,',
        'admin' => 'pr.api.api.comm-template.PATCH,pr.api.api.comm-template.POST,pr.api.api.single-language-comm-template.GET,pr.api.comm-fields,pr.api.comm-template.structure,pr.api.status,pr.respondent.show-deleted',
        'siteadmin' => 'pr.setup.project-information.cacheclean,pr.setup.project-information.configcacheclean,pr.setup.agenda.info.export,pr.track-builder.track-maintenance.import,pr.staff.see.all,pr.respondent.multiorg,
            pr.systemuser.seepwd,pr.survey-maintenance.answer-groups',
        'super' => 'pr.organization-switch,pr.staff.edit.all,pr.group.switch',
    ];

    /**
     * @var array|string[] Just remove all
     */
    protected array $removedPrivileges = [
        'pr.ask',
        'pr.contact',
        'pr.contact.bugs',
        'pr.contact',
        'pr.contact.gems',
        'pr.contact.support',
        'pr.cron.job',
        'pr.islogin',
        'pr.nologin',
        'pr.participate.subscribe',
        'pr.participate.unsubscribe',
        'pr.prediction.model-mapping',
        'pr.setup.project-information.upgrade',
        'pr.site-maint',
        'pr.site-maint.create',
        'pr.site-maint.delete',
        'pr.site-maint.edit',
        'pr.site-maint.export',
        'pr.site-maint.import',
        'pr.site-maint.lock',
//        '',
//        '',
    ];

    public function getDescription(): string|null
    {
        return 'Update roles in gems__roles for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20240203000001;
    }

    /**
     * @inheritDoc
     */
    public function up(): array
    {
        $output[] = "DELETE FROM gems__roles WHERE grl_name = 'nologin'";

        $output[] = "UPDATE gems__roles SET grl_privileges = CONCAT(',', grl_privileges, ',')";

        foreach ($this->removedPrivileges as $removed) {
            $output[] = "UPDATE gems__roles SET grl_privileges = REPLACE(grl_privileges, ',$removed,', ',') WHERE grl_privileges LIKE '%,$removed,%'";
        }

        foreach ($this->fullRoleNameChanges as $old => $new) {
            $output[] = "UPDATE gems__roles SET grl_privileges = REPLACE(grl_privileges, ',$old,', ',$new,') WHERE grl_privileges LIKE '%,$old,%' AND grl_privileges NOT LIKE '%,$new,%'";
        }

        foreach ($this->extraRoles as $current => $extra) {
            $output[] = "UPDATE gems__roles SET grl_privileges = REPLACE(grl_privileges, ',$current,', ',$current,$extra,') WHERE grl_privileges LIKE '%,$current,%' AND grl_privileges NOT LIKE '%,$extra,%'";
        }

        $output[] = "UPDATE gems__roles SET grl_privileges = TRIM(',' FROM grl_privileges)";

        foreach ($this->newPrivileges as $role => $privilegeList) {
            $privileges = array_map('trim', explode(',', $privilegeList));
            foreach ($privileges as $privilege) {
                if ($privilege) {
                    $output[] = "UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',', '$privilege') WHERE (grl_name = '$role' AND grl_privileges NOT LIKE '%,$privilege%')";
                }
            }
        }

        return $output;
    }
}