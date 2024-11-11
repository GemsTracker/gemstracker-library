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
        'pr.respondent' => 'pr.respondent.index,pr.respondent.show',

        'pr.calendar' => 'pr.calendar.index',
        'pr.comm.template.edit' => 'pr.setup.communication.template.edit,pr.api.api.comm-template.GET,pr.api.api.comm-template.PATCH,pr.api.api.comm-template.POST,pr.api.comm-template.structure',
        'pr.database.patches' => 'pr.setup.database.patches.index,pr.setup.database.patches.new,pr.setup.database.patches.run,pr.setup.database.patches.run-all,pr.setup.database.patches.show,pr.setup.database.seeds.index,pr.setup.database.seeds.new,pr.setup.database.seeds.run.pr.setup.database.seeds.run-all,pr.setup.database.seeds.show',
//        'pr.group.switch' => 'pr.group.switch',
        'pr.log.maintenance' => 'pr.setup.log.activity.index,pr.setup.log.activity.show',
        'pr.maintenance.clean-cache' => 'pr.setup.project-information.cacheclean',
//        'pr.maintenance.maintenance-mode' => 'pr.maintenance.maintenance-mode',
        'pr.option.2factor' => 'pr.option.two-factor',
        'pr.option.edit' => 'pr.option.overview,pr.option.edit',
        'pr.option.password' => 'pr.option.edit-auth',
//        'pr.organization-switch' => 'pr.organization-switch',
        'pr.plan.compliance' => 'pr.overview.compliance.index',
        'pr.plan.compliance.export' => 'pr.overview.compliance.export',
        'pr.plan.consent' => 'pr.overview.consent-plan.index',
        'pr.plan.consent.export' => 'pr.overview.consent-plan.export',
        'pr.plan.fields' => 'pr.overview.field-overview.index,pr.overview.field-report.index',
        'pr.plan.fields.export' => 'pr.overview.field-overview.export,pr.overview.field-report.export',
//        'pr.plan.mail-as-application' => 'pr.plan.mail-as-application',
        'pr.plan.overview' => 'pr.overview.overview-plan.index',
        'pr.plan.overview.export' => 'pr.overview.overview-plan.export',
        'pr.plan.respondent' => 'pr.overview.respondent-plan.index',
        'pr.plan.respondent.export' => 'pr.overview.respondent-plan.export',
        'pr.plan.summary' => 'pr.overview.summary.index',
        'pr.plan.summary.export' => 'pr.overview.summary.export',
        'pr.plan.token' => 'pr.overview.token-plan.index',
        'pr.plan.token.export' => 'pr.overview.token-plan.export',
        'pr.project' => 'pr.project.surveys.index,pr.project.surveys.show,pr.project.tracks.index,pr.project.tracks.show',
        'pr.project-information' => 'pr.setup.project-information.changelog,pr.setup.project-information.changelog-gems,pr.setup.project-information.errors,pr.setup.project-information.index,pr.setup.project-information.monitor,pr.setup.project-information.php,pr.setup.project-information.php-errors,pr.setup.project-information.project,pr.setup.project-information.session',
//        'pr.project.questions' => '',
        'pr.respondent.change-org' => 'pr.respondent.change-organization',
        'pr.source' => 'pr.track-builder.source.index,pr.track-builder.source.show',
        'pr.source.check-answers' => 'pr.track-builder.source.check',
        'pr.source.check-answers-all' => 'pr.track-builder.source.check-all',
        'pr.source.check-attributes' => 'pr.track-builder.source.attributes',
        'pr.source.check-attributes-all' => 'pr.track-builder.source.attributes-all',
        'pr.staff' => 'pr.setup.access.staff.index,pr.setup.access.staff.show',
        'pr.staff-log' => 'pr.setup.access.staff.log.index,pr.setup.access.staff.log.show',
        'pr.staff.reactivate' => 'pr.setup.access.staff.active-toggle',
        'pr.survey-maintenance' => 'pr.track-builder.survey-maintenance.index,pr.track-builder.survey-maintenance.show',
        'pr.survey-maintenance.code-book-export' => 'pr.track-builder.survey-maintenance.export-codebook.export',
        'pr.systemuser' => 'pr.setup.access.system-user.index,pr.setup.access.system-user.show',
        'pr.systemuser.reactivate' => 'pr.setup.access.system-user.active-toggle',
        'pr.token.mail' => 'pr.respondent.tracks.token.email',
//        'pr.token.mail.freetext' => 'pr.token.mail.freetext',
        'pr.track.answers' => 'pr.respondent.tracks.check-track-answers',
        'pr.track.check' => 'pr.respondent.tracks.check-track',
        ];

    /**
     * @var array|string[] role start name => nre role start name
     */
    protected array $mainRoleNameChanges = [
        'pr.agenda-activity' => 'pr.setup.agenda.activity',
        'pr.agenda-diagnosis' => 'pr.setup.agenda.diagnosis',
        'pr.agenda-filters' => 'pr.setup.agenda.filter',
        'pr.agenda-procedure' => 'pr.setup.agenda.procedure',
        'pr.agenda-staff' => 'pr.setup.agenda.staff',
        'pr.appointments' => 'pr.respondent.appointments',
        'pr.chartsetup' => 'pr.track-builder.chartconfig',
        'pr.comm.job' => 'pr.setup.communication.job',
        'pr.comm.messenger' => 'pr.setup.communication.messenger',
        'pr.comm.template' => 'pr.setup.communication.template',
        'pr.conditions' => 'pr.track-builder.condition',
        'pr.consent' => 'pr.setup.codes.consent',
        'pr.episodes' => 'pr.respondent.episodes-of-care',
        'pr.group' => 'pr.setup.access.groups',
        'pr.locations' => 'pr.setup.agenda.location',
        'pr.log' => 'pr.setup.log.activity',
        'pr.log.files' => 'pr.setup.log.files',
        'pr.log.files.upload' => 'pr.setup.log.maintenance',
        'pr.log.maintenance' => 'pr.setup.log.activity',
        'pr.mail.log' => 'pr.setup.communication.log',
        'pr.mail.server' => 'pr.setup.communication.server',
        'pr.mailcode' => 'pr.setup.codes.mail-code',
        'pr.organization' => 'pr.setup.access.organizations',
        'pr.reception' => 'pr.setup.codes.reception',
        'pr.respondent-commlog' => 'pr.respondent.communication-log',
        'pr.respondent-log' => 'pr.respondent.activity-log',
        'pr.role' => 'pr.setup.access.roles',
        'pr.source' => 'pr.track-builder.source',
        'pr.staff' => 'pr.setup.access.staff',
        'pr.survey' => 'pr.respondent.tokens',
        'pr.survey-maintenance' => 'pr.track-builder.survey-maintenance',
        'pr.systemuser' => 'pr.setup.access.system-user',
        'pr.token' => 'pr.respondent.tracks.token',
        'pr.track' => 'pr.respondent.tracks',
        'pr.track-maintenance' => 'pr.track-builder.track-maintenance',
//        '' => '',
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

        foreach ($this->mainRoleNameChanges as $old => $new) {
            $output[] = "UPDATE gems__roles SET grl_privileges = REPLACE(grl_privileges, ',$old', ',$new') WHERE grl_privileges LIKE '%,$old%' AND grl_privileges NOT LIKE '%$new%'";
            // Split bare old name into show and index
            $output[] = "UPDATE gems__roles SET grl_privileges = REPLACE(grl_privileges, ',$new,', ',$new.index,$new.show,') WHERE grl_privileges LIKE '%,$new,%' AND grl_privileges NOT LIKE '%,$new.show,%'";
        }

        foreach ($this->extraRoles as $current => $extra) {
            $output[] = "UPDATE gems__roles SET grl_privileges = REPLACE(grl_privileges, ',$current,', ',$current,$extra,') WHERE grl_privileges LIKE '%,$current,%' AND grl_privileges NOT LIKE '%,$extra,%'";
        }

        $output[] = "UPDATE gems__roles SET grl_privileges = TRIM(',' FROM grl_privileges)";

        return $output;
    }
}