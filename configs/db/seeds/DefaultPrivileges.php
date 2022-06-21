<?php


use Phinx\Seed\AbstractSeed;

class DefaultPrivileges extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'grl_id_role' => 800,
                'grl_name' => 'nologin',
                'grl_description' => 'nologin',
                'grl_parents' => null,
                'grl_privileges' => 'pr.contact.bugs,pr.contact.support,pr.cron.job,pr.nologin,pr.respondent.ask,pr.respondent.lost',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 801,
                'grl_name' => 'guest',
                'grl_description' => 'guest',
                'grl_parents' => null,
                'grl_privileges' => 'pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin,pr.respondent,pr.respondent.ask,pr.ask',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 802,
                'grl_name' => 'respondent',
                'grl_description' => 'respondent',
                'grl_parents' => null,
                'grl_privileges' => 'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 803,
                'grl_name' => 'security',
                'grl_description' => 'security',
                'grl_parents' => '801',
                'grl_privileges' => 'pr.log,pr.log.files,pr.log.files.download,pr.log.maintenance,pr.log.maintenance.edit,
    ,pr.mail.log,
    ,pr.option.edit,pr.option.password,
    ,pr.respondent.show-deleted,pr.respondent.who,
    ,pr.respondent-commlog,pr.respondent-log,
    ,pr.staff,pr.staff.see.all,
    ,pr.staff-log',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 804,
                'grl_name' => 'staff',
                'grl_description' => 'staff',
                'grl_parents' => '801',
                'grl_privileges' => 'pr.option.edit,pr.option.password,
    ,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.fields,pr.plan.respondent,pr.plan.summary,pr.plan.token,
    ,pr.project,pr.project.questions,
    ,pr.respondent.create,pr.respondent.change-consent,pr.respondent.edit,pr.respondent.select-on-track,pr.respondent.who,
    ,pr.respondent-commlog,pr.respondent-log,
    ,pr.survey,
    ,pr.token,pr.token.answers,pr.token.correct,pr.token.delete,pr.token.edit,pr.token.mail,pr.token.print,
    ,pr.track,pr.track.answers,pr.track.create,pr.track.delete,pr.track.edit',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 805,
                'grl_name' => 'physician',
                'grl_description' => 'physician',
                'grl_parents' => '804',
                'grl_privileges' => '',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 806,
                'grl_name' => 'researcher',
                'grl_description' => 'researcher',
                'grl_parents' => null,
                'grl_privileges' => 'pr.contact.bugs,pr.contact.gems,pr.contact.support,
    ,pr.cron.job,
    ,pr.export,pr.export.export,
    ,pr.islogin,
    ,pr.plan.consent,pr.plan.consent.export,
    ,pr.upgrade,
    ,pr.option.password,pr.option.edit,pr.organization-switch,
    ,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.fields,pr.plan.respondent,pr.plan.summary,pr.plan.token',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 807,
                'grl_name' => 'admin',
                'grl_description' => 'local admin',
                'grl_parents' => '801,803,804,805,806',
                'grl_privileges' => 'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.export,pr.export.export,pr.export-html,pr.export.code-book-export,
    ,pr.group,
    ,pr.mail.log,
    ,pr.organization,pr.organization-switch,
    ,pr.plan.compliance.export,pr.plan.overview.export,pr.plan.fields.export,
    ,pr.plan.respondent,pr.plan.respondent.export,pr.plan.summary.export,pr.plan.token.export,
    ,pr.project-information,
    ,pr.reception,pr.reception.create,pr.reception.edit,
    ,pr.respondent.delete,pr.respondent.result,pr.respondent.show-deleted,pr.respondent.undelete,
    ,pr.role,
    ,pr.staff,pr.staff.create,pr.staff.deactivate,pr.staff.edit,pr.staff.reactivate,pr.staff.see.all,
    ,pr.staff-log,
    ,pr.source,
    ,pr.survey-maintenance,pr.survey-maintenance.answer-import,
    ,pr.token.mail.freetext,pr.token.undelete,
    ,pr.track.check,pr.track.insert,pr.track.undelete,
    ,pr.track-maintenance,pr.track-maintenance.create,pr.track-maintenance.edit,pr.track-maintenance.export,
    ,pr.track-maintenance.import,pr.track-maintenance.trackperorg,
    ,pr.conditions,pr.conditions.create,pr.conditions.edit',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 808,
                'grl_name' => 'siteadmin',
                'grl_description' => 'site admin',
                'grl_parents' => '801,803,804,805,806,807',
                'grl_privileges' => 'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.export,pr.export.export,pr.export-html,
    ,pr.group,pr.group.switch,
    ,pr.mail.log,
    ,pr.maintenance.clean-cache,
    ,pr.organization,pr.organization.check-all,pr.organization.check-org,pr.organization-switch,
    ,pr.plan.compliance.export,pr.plan.overview.export,pr.plan.fields.export,
    ,pr.plan.respondent,pr.plan.respondent.export,pr.plan.summary.export,pr.plan.token.export,
    ,pr.project-information,
    ,pr.reception,pr.reception.create,pr.reception.edit,
    ,pr.respondent.change-org,pr.respondent.delete,pr.respondent.export-html,pr.respondent.result,pr.respondent.show-deleted,pr.respondent.undelete,
    ,pr.role,
    ,pr.staff,pr.staff.create,pr.staff.deactivate,pr.staff.edit,pr.staff.edit.all,pr.staff.reactivate,pr.staff.see.all,
    ,pr.staff-log,
    ,pr.source,pr.source.check-answers,pr.source.check-answers-all,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.synchronize,pr.source.synchronize-all,
    ,pr.survey-maintenance,pr.survey-maintenance.answer-import,pr.survey-maintenance.answer-import,pr.survey-maintenance.check,pr.survey-maintenance.check-all,pr.survey-maintenance.edit.
    ,pr.token.mail.freetext,pr.token.undelete,
    ,pr.track.check,pr.track.insert,pr.track.undelete,
    ,pr.track-maintenance,pr.track-maintenance.check,pr.track-maintenance.check-all,pr.track-maintenance.create,pr.track-maintenance.edit,pr.track-maintenance.export,
    ,pr.track-maintenance.import,pr.track-maintenance.trackperorg,
    ,pr.conditions,pr.conditions.create,pr.conditions.edit',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
            [
                'grl_id_role' => 809,
                'grl_name' => 'super',
                'grl_description' => 'super',
                'grl_parents' => '801,803,804,805,806,807,808',
                'grl_privileges' => 'pr.agenda-activity,pr.agenda-activity.cleanup,pr.agenda-activity.create,pr.agenda-activity.delete,pr.agenda-activity.edit,
    ,pr.agenda-filters,pr.agenda-filters.create,pr.agenda-filters.delete,pr.agenda-filters.edit,
    ,pr.agenda-procedure,pr.agenda-procedure.cleanup,pr.agenda-procedure.create,pr.agenda-procedure.delete,pr.agenda-procedure.edit,
    ,pr.agenda-staff,pr.agenda-staff.create,pr.agenda-staff.delete,pr.agenda-staff.edit,
    ,pr.comm.job.create,pr.comm.job.edit,pr.comm.job.delete,
    ,pr.consent.delete,
    ,pr.database,pr.database.create,pr.database.delete,pr.database.execute,pr.database.patches,
    ,pr.episodes.rawdata,
    ,pr.file-import,pr.file-import.import,
    ,pr.group.create,pr.group.edit,
    ,pr.locations,pr.locations.cleanup,pr.locations.create,pr.locations.delete,pr.locations.edit,
    ,pr.log.files,pr.log.files.download,
    ,pr.mail.server,pr.mail.server.create,pr.mail.server.delete,pr.mail.server.edit,
    ,pr.maintenance.maintenance-mode,
    ,pr.organization.create,pr.organization.edit,
    ,pr.plan.mail-as-application,pr.reception.delete,
    ,pr.respondent.multiorg,
    ,pr.role.create,pr.role.edit,
    ,pr.site-maint,pr.site-maint.create,pr.site-maint.delete,,pr.site-maint.edit,,pr.site-maint.lock,
    ,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.create,pr.source.edit,pr.source.synchronize,
    ,pr.source.synchronize-all,
    ,pr.staff.edit.all,pr.staff.switch-user,
    ,pr.systemuser,pr.systemuser.create,pr.systemuser.deactivate,pr.systemuser.edit,pr.staff.systemuser,pr.systemuser.see.all,
    ,pr.survey-maintenance.edit,
    ,pr.templates,
    ,pr.track-maintenance.trackperorg,pr.track-maintenance.delete,
    ,pr.conditions.delete,
    ,pr.upgrade,pr.upgrade.all,pr.upgrade.one,pr.upgrade.from,pr.upgrade.to',
                'grl_changed' => $now->format('Y-m-d H:i:s'),
                'grl_changed_by' => 1,
                'grl_created' => $now->format('Y-m-d H:i:s'),
                'grl_created_by' => 1
            ],
        ];

        $roles = $this->table('gems__roles');
        $roles->insert($data)
              ->saveData();
    }
}
