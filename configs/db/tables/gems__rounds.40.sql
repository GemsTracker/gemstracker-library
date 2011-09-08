
CREATE TABLE if not exists gems__rounds (
        gro_id_round bigint unsigned not null auto_increment,

        gro_id_track bigint unsigned not null references gems__track (gtr_id_track),
        gro_id_order int not null default 10,

        gro_id_survey bigint unsigned not null references gems__surveys (gsu_id_survey),

        -- Survey_name is a temp copy from __surveys, needed by me to keep an overview as
        -- long as no track editor exists.
        gro_survey_name varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gro_round_description varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gro_changed_event varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- depreciated
        gro_valid_after char(6) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gro_valid_for char(6) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gro_used_date char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'A',
        gro_used_date_order int(4) null default 10,
        gro_used_date_field varchar(16) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        -- end of depreciated

        gro_active boolean not null default 1,

        gro_changed timestamp not null default current_timestamp on update current_timestamp,
        gro_changed_by bigint unsigned not null,
        gro_created timestamp not null,
        gro_created_by bigint unsigned not null,

        PRIMARY KEY (gro_id_round),
        INDEX (gro_id_track, gro_id_order)
    )
    ENGINE=InnoDB
    auto_increment = 40000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

