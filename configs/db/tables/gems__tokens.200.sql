
CREATE TABLE if not exists gems__tokens (
        gto_id_token            varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gto_id_respondent_track bigint unsigned not null references gems__respondent2track (gr2t_id_respondent_track),
        gto_id_round            bigint unsigned not null references gems__rounds (gro_id_round),

        -- non-changing fields calculated from previous two:
        gto_id_respondent       bigint unsigned not null references gems__respondents (grs_id_user),
        gto_id_organization     bigint unsigned not null references gems__organizations (gor_id_organization),
        gto_id_track            bigint unsigned not null references gems__tracks (gtr_id_track),

        -- values initially filled from gems__rounds, but that may get different values later on
        gto_id_survey           bigint unsigned not null references gems__surveys (gsu_id_survey),

        -- values initially filled from gems__rounds, but that might get different values later on, but but not now
        gto_round_order         int not null default 10,
        gto_round_description   varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        --- fields for relations
        gto_id_relationfield  bigint(2) null default null,
        gto_id_relation       bigint(2) null default null,

        -- real data
        gto_valid_from          datetime,
        gto_valid_from_manual   boolean not null default 0,
        gto_valid_until         datetime,
        gto_valid_until_manual  boolean not null default 0,
        gto_mail_sent_date      date,
        gto_mail_sent_num       int(11) unsigned not null default 0,
        -- gto_next_mail_date      date,  -- deprecated

        gto_start_time          datetime,
        gto_in_source           boolean not null default 0,
        gto_by                  bigint(20) unsigned NULL,

        gto_completion_time     datetime,
        gto_duration_in_sec     bigint(20) unsigned NULL,
        -- gto_followup_date       date, -- deprecated
        gto_result              varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gto_comment             text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gto_reception_code      varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
                                references gems__reception_codes (grc_id_reception_code),

        gto_return_url          varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gto_changed             timestamp not null default current_timestamp on update current_timestamp,
        gto_changed_by          bigint unsigned not null,
        gto_created             timestamp not null,
        gto_created_by          bigint unsigned not null,

        PRIMARY KEY (gto_id_token),
        INDEX (gto_id_organization),
        INDEX (gto_id_respondent),
        INDEX (gto_id_survey),
        INDEX (gto_id_track),
        INDEX (gto_id_round),
        INDEX (gto_in_source),
        INDEX (gto_reception_code),
        INDEX (gto_id_respondent_track, gto_round_order),
        INDEX (gto_valid_from, gto_valid_until),
        INDEX (gto_completion_time),
        INDEX (gto_by),
        INDEX (gto_round_order),
        INDEX (gto_created)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

