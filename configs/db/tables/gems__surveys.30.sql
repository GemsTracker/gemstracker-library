
CREATE TABLE if not exists gems__surveys (
        gsu_id_survey int unsigned not null auto_increment,
        gsu_survey_name varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gsu_survey_description varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_surveyor_id int(11),
        gsu_surveyor_active boolean not null default 1,

        -- depreciated
        gsu_survey_table varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_token_table varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- end depreciated

        gsu_survey_pdf            varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_beforeanswering_event varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_completed_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_display_event         varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_id_source int unsigned not null
                references gems__sources (gso_id_source),
        gsu_active boolean not null default 0,
        gsu_status varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- depreciated
        gsu_staff boolean not null default 0,
        -- end depreciated

        gsu_id_primary_group bigint unsigned null
                references gems__groups (ggp_id_group),

        -- depreciated
        gsu_id_user_field varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_completion_field varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'submitdate',
        gsu_followup_field varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'submitdate',
        -- end depreciated

        gsu_result_field   varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_agenda_result  varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsu_duration       varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gsu_code           varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gsu_changed timestamp not null default current_timestamp on update current_timestamp,
        gsu_changed_by bigint unsigned not null,
        gsu_created timestamp not null,
        gsu_created_by bigint unsigned not null,

        PRIMARY KEY(gsu_id_survey),
        INDEX (gsu_active),
        INDEX (gsu_surveyor_active),
        INDEX (gsu_code)
    )
    ENGINE=InnoDB
    auto_increment = 500
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

