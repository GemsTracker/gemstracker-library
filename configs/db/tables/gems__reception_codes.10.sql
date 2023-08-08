
CREATE TABLE if not exists gems__reception_codes (
      grc_id_reception_code varchar(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
      grc_description       varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,

      grc_success           boolean not null default 0,

      grc_for_surveys       tinyint not null default 0,
      grc_redo_survey       tinyint not null default 0,
      grc_for_tracks        boolean not null default 0,
      grc_for_respondents   boolean not null default 0,
      grc_overwrite_answers boolean not null default 0,
      grc_active            boolean not null default 1,

      grc_changed    timestamp not null default current_timestamp on update current_timestamp,
      grc_changed_by bigint unsigned not null,
      grc_created    timestamp not null default current_timestamp,
      grc_created_by bigint unsigned not null,

      PRIMARY KEY (grc_id_reception_code),
      INDEX (grc_success)
   )
   ENGINE=InnoDB
   auto_increment = 1
   CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
