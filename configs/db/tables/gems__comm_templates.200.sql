
CREATE TABLE if not exists gems__comm_templates (
      gct_id_template bigint unsigned not null, 

      gct_name    varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gct_target    varchar(16) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      gct_code    varchar(64)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL
      
      gct_changed timestamp not null default current_timestamp on update current_timestamp,
      gct_changed_by bigint unsigned not null,
      gct_created timestamp not null default '0000-00-00 00:00:00',
      gct_created_by bigint unsigned not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE KEY (gct_name)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 20
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (20, 'Questions for your treatement at {organization}', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (21, 'Reminder: your treatement at {organization}', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
