
CREATE TABLE if not exists gems__roles (
      grl_id_role bigint unsigned not null auto_increment,
      grl_name varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      grl_description varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      grl_parents text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
      -- The grl_parents is a comma-separated list of parents for this role

      grl_privileges text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
      -- The grl_privilege is a comma-separated list of privileges for this role

      grl_changed timestamp not null default current_timestamp on update current_timestamp,
      grl_changed_by bigint unsigned not null,
      grl_created timestamp not null,
      grl_created_by bigint unsigned not null,

      PRIMARY KEY(grl_id_role)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- default roles/privileges

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (800, 'nologin', 'nologin', null,
    'pr.contact.bugs,pr.contact.support,pr.cron.job,pr.nologin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (801, 'guest', 'guest', null,
    'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin,pr.respondent',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (802, 'respondent','respondent', null,
    'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (803, 'security', 'security', '801',
    'pr.log,pr.log.files,pr.log.files.download,pr.log.maintenance,pr.log.maintenance.edit,
    ,pr.mail.log,
    ,pr.option.edit,pr.option.password,
    ,pr.respondent.show-deleted,pr.respondent.who,
    ,pr.respondent-commlog,pr.respondent-log,
    ,pr.staff,pr.staff.see.all,
    ,pr.staff-log',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (804, 'staff', 'staff', '801',
    'pr.option.edit,pr.option.password,
    ,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.fields,pr.plan.respondent,pr.plan.summary,pr.plan.token,
    ,pr.project,pr.project.questions,
    ,pr.respondent.create,pr.respondent.edit,pr.respondent.select-on-track,pr.respondent.who,
    ,pr.respondent-commlog,pr.respondent-log,
    ,pr.survey,
    ,pr.token,pr.token.answers,pr.token.correct,pr.token.delete,pr.token.edit,pr.token.mail,pr.token.print,
    ,pr.track,pr.track.answers,pr.track.create,pr.track.delete,pr.track.edit',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (805, 'physician', 'physician', '804',
    '',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (806, 'researcher', 'researcher', null,
    'pr.contact.bugs,pr.contact.gems,pr.contact.support,
    ,pr.cron.job,
    ,pr.export,pr.export.export,
    ,pr.islogin,
    ,pr.plan.consent,pr.plan.consent.export,
	,pr.upgrade,
    ,pr.option.password,pr.option.edit,pr.organization-switch,
	,pr.plan.compliance,pr.plan.consent,pr.plan.overview,pr.plan.fields,pr.plan.respondent,pr.plan.summary,pr.plan.token',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (807, 'admin', 'local admin', '801,803,804,805,806',
    'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.export,pr.export.export,pr.export-html,
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
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (808, 'siteadmin', 'site admin', '801,803,804,805,806,807',
    'pr.comm.job,
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
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (809, 'super', 'super', '801,803,804,805,806,807,808',
    'pr.agenda-activity,pr.agenda-activity.cleanup,pr.agenda-activity.create,pr.agenda-activity.delete,pr.agenda-activity.edit,
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
    ,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.create,pr.source.edit,pr.source.synchronize,
    ,pr.source.synchronize-all,
    ,pr.staff.edit.all,pr.staff.switch-user,
    ,pr.systemuser,pr.systemuser.create,pr.systemuser.deactivate,pr.systemuser.edit,pr.staff.systemuser,pr.systemuser.see.all,
    ,pr.survey-maintenance.edit,
    ,pr.templates,
    ,pr.track-maintenance.trackperorg,pr.track-maintenance.delete,
    ,pr.conditions.delete,
    ,pr.upgrade,pr.upgrade.all,pr.upgrade.one,pr.upgrade.from,pr.upgrade.to',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);