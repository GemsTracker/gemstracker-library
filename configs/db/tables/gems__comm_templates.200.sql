
CREATE TABLE if not exists gems__comm_templates (
      gct_id_template bigint unsigned not null AUTO_INCREMENT,

      gct_name        varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gct_target      varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gct_code        varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

      gct_changed     timestamp not null default current_timestamp on update current_timestamp,
      gct_changed_by  bigint unsigned not null,
      gct_created     timestamp not null default '0000-00-00 00:00:00',
      gct_created_by  bigint unsigned not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE KEY (gct_name)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 20
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (15, 'Questions for your treatement at {organization}', 'token', null,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (16, 'Reminder: your treatement at {organization}', 'token', null,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (17, 'Global Password reset', 'staffPassword', 'passwordReset', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (18, 'Global Account created', 'staffPassword', 'accountCreate', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (19, 'Linked account created', 'staff', 'linkedAccountCreated', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
