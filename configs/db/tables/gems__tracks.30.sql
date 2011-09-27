
CREATE TABLE if not exists gems__tracks (
        gtr_id_track int unsigned not null auto_increment,
        gtr_track_name varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
                not null unique key,

        gtr_track_info varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gtr_date_start date not null,
        gtr_date_until date null,

        gtr_active boolean not null default 0,
        gtr_survey_rounds int unsigned not null default 0,
        gtr_track_type char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'T',

        -- depreciated
        gtr_track_model varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'TrackModel',
        -- end depreciated

        gtr_track_class varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        -- Yes, quick and dirty, will correct later (probably)
        gtr_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gtr_changed timestamp not null default current_timestamp on update current_timestamp,
        gtr_changed_by bigint unsigned not null,
        gtr_created timestamp not null,
        gtr_created_by bigint unsigned not null,

        PRIMARY KEY (gtr_id_track),
        INDEX (gtr_active)
    )
    ENGINE=InnoDB
    auto_increment = 7000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

