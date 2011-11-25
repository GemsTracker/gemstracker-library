
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

INSERT INTO gems__roles (grl_name, grl_description, grl_privileges, grl_parents, grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    ('nologin','nologin','pr.contact.bugs,pr.contact.support,pr.nologin','', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('guest','guest','pr.ask,pr.contact.bugs,pr.contact.support,pr.islogin,pr.respondent','', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('respondent','respondent','','guest', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('security','security','','guest', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('staff','staff','pr.option.edit,pr.option.password,pr.plan,pr.plan.overview,pr.plan.token,pr.project,pr.project.questions,pr.respondent.create,pr.respondent.edit,pr.respondent.who,pr.setup,pr.staff,pr.survey,pr.survey.create,pr.token,pr.token.answers,pr.token.delete,pr.token.edit,pr.token.mail,pr.token.print,pr.track,pr.track.create,pr.track.delete,pr.track.edit,pr.respondent.reportdeath','guest', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('physician','physician','','staff', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('researcher','researcher','pr.invitation,pr.result,pr.islogin','', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('admin','admin','pr.consent,pr.consent.create,pr.consent.edit,pr.group,pr.role,pr.mail,pr.mail.create,pr.mail.delete,pr.mail.edit,pr.mail.log,pr.organization,pr.organization-switch,pr.plan.overview.excel,pr.plan.respondent,pr.plan.respondent.excel,pr.plan.token.excel,pr.project-information,pr.reception,pr.reception.create,pr.reception.edit,pr.respondent.choose-org,pr.respondent.delete,pr.respondent.result,pr.source,pr.staff.create,pr.staff.delete,pr.staff.edit,pr.staff.see.all,pr.survey-maintenance,pr.track-maintenance,pr.token.mail.freetext','staff,researcher,security', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('super','super','pr.consent.delete,pr.country,pr.country.create,pr.country.delete,pr.country.edit,pr.database,pr.database.create,pr.database.delete,pr.database.edit,pr.database.execute,pr.database.patches,pr.group.create,pr.group.edit,pr.language,pr.mail.server,pr.mail.server.create,pr.mail.server.delete,pr.mail.server.edit,pr.organization.create,pr.organization.edit,pr.plan.choose-org,pr.plan.mail-as-application,pr.reception.delete,pr.role.create,pr.role.edit,pr.source.create,pr.source.edit,pr.source.synchronize,pr.source.synchronize-all,pr.staff.edit.all,pr.survey-maintenance.edit,pr.track-maintenance.create,pr.track-maintenance.edit,pr.maintenance','admin', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
