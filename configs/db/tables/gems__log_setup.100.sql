
CREATE TABLE if not exists gems__log_setup (
        gls_id_action       int unsigned not null auto_increment,
        gls_name            varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null unique,

        gls_when_no_user    boolean not null default 0,
        gls_on_action       boolean not null default 0,
        gls_on_post         boolean not null default 0,
        gls_on_change       boolean not null default 1,

        gls_changed         timestamp not null default current_timestamp on update current_timestamp,
        gls_changed_by      bigint unsigned not null,
        gls_created         timestamp not null,
        gls_created_by      bigint unsigned not null,

        PRIMARY KEY (gls_id_action),
        INDEX (gls_name)
    )
    ENGINE=InnoDB
    auto_increment = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__log_setup (gls_name, gls_when_no_user, gls_on_action, gls_on_post, gls_on_change,
        gls_changed, gls_changed_by, gls_created, gls_created_by)
    VALUES
        ('comm-job.cron-lock',                  1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('comm-job.execute',                    1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('comm-job.execute-all',                1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('cron.index',                          1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.patch',                      0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.run',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.run-all',                    0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.run-sql',                    0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('database.view',                       0, 1, 0, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('export.index',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('file-import.answers-import',          1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('index.login',                         0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('index.logoff',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('index.resetpassword',                 1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('participate.subscribe',               0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('participate.unsubscribe',             0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('project-information.maintenance',     1, 1, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('respondent.show',                     0, 1, 0, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.attributes',                   0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.attributes-all',               0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.check',                        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.check-all',                    0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.synchronize',                  0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('source.synchronize-all',              0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('survey-maintenance.check',            0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('survey-maintenance.check-all',        0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('token.answered',                      1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('token.data-changed',                  1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-all-answers',             1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-all-tracks',              1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-token-answers',           1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-track',                   1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.check-track-answers',           1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.delete-track',                  0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.edit-track',                    0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.recalc-all-fields',             1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track.recalc-fields',                 1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.check-all',         0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.check-track',       0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.export',            1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.import',            1, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.recalc-all-fields', 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('track-maintenance.recalc-fields',     0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-all',                 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-from',                0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-last',                0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-one',                 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
        ('upgrade.execute-to',                  0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
