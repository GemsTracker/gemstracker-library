
CREATE TABLE if not exists gems__comm_templates (
      gct_id_template bigint unsigned not null AUTO_INCREMENT,

      gct_name        varchar(120) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
      gct_target      varchar(32) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
      gct_code        varchar(64)  CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,

      gct_changed     timestamp not null default current_timestamp on update current_timestamp,
      gct_changed_by  bigint unsigned not null,
      gct_created     timestamp not null default current_timestamp,
      gct_created_by  bigint unsigned not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE KEY (gct_name)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 20
   CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

