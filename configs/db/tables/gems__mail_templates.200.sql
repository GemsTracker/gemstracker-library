
CREATE TABLE if not exists gems__mail_templates (
      gmt_id_message bigint unsigned not null auto_increment,

      gmt_subject    varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gmt_body       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      -- Yes, quick and dirty, will correct later (probably)
      gmt_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

      gmt_changed timestamp not null default current_timestamp on update current_timestamp,
      gmt_changed_by bigint unsigned not null,
      gmt_created timestamp not null default '0000-00-00 00:00:00',
      gmt_created_by bigint unsigned not null,

      PRIMARY KEY (gmt_id_message),
      UNIQUE KEY (gmt_subject)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 20
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

