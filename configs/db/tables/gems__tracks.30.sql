
CREATE TABLE if not exists gems__tracks (
        gtr_id_track                int unsigned not null auto_increment,
        gtr_track_name              varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null unique key,
        gtr_external_description    varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',

        gtr_track_info              varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',
        gtr_code                    varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,

        gtr_date_start              date not null,
        gtr_date_until              date null,

        gtr_active                  boolean not null default 0,
        gtr_survey_rounds           int unsigned not null default 0,

        gtr_track_class             varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        gtr_beforefieldupdate_event varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',
        gtr_calculation_event       varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',
        gtr_completed_event         varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',
        gtr_fieldupdate_event       varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',

        -- Yes, quick and dirty
        gtr_organizations           varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',

        gtr_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gtr_changed_by              bigint unsigned not null,
        gtr_created                 timestamp not null,
        gtr_created_by              bigint unsigned not null,

        PRIMARY KEY (gtr_id_track),
        INDEX (gtr_track_name),
        INDEX (gtr_active),
        INDEX (gtr_track_class)
    )
    ENGINE=InnoDB
    auto_increment = 7000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

