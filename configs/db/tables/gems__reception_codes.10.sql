
CREATE TABLE if not exists gems__reception_codes (
      grc_id_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
      grc_description       varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

      grc_success           boolean not null default 0,

      grc_for_surveys       tinyint not null default 0,
      grc_redo_survey       tinyint not null default 0,
      grc_for_tracks        boolean not null default 0,
      grc_for_respondents   boolean not null default 0,
      grc_overwrite_answers boolean not null default 0,
      grc_active            boolean not null default 1,

      grc_changed    timestamp not null default current_timestamp on update current_timestamp,
      grc_changed_by bigint unsigned not null,
      grc_created    timestamp not null,
      grc_created_by bigint unsigned not null,

      PRIMARY KEY (grc_id_reception_code),
      INDEX (grc_success)
   )
   ENGINE=InnoDB
   auto_increment = 1
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
    ('OK', '', 1, 1, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 1, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('stop', 'Stop surveys', 0, 2, 0, 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
