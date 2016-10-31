
CREATE TABLE if not exists gems__respondent2org (
        gr2o_patient_nr         varchar(15) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gr2o_id_organization    bigint unsigned not null references gems__organizations (gor_id_organization),

        gr2o_id_user            bigint unsigned not null references gems__respondents (grs_id_user),

        -- gr2o_id_physician       bigint unsigned null references gems_staff (gsf_id_user),

        -- gr2o_treatment          varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gr2o_mailable           boolean not null default 1,
        gr2o_comments           text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gr2o_consent            varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'Unknown'
                                references gems__consents (gco_description),
        gr2o_reception_code     varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
                                references gems__reception_codes (grc_id_reception_code),

        gr2o_opened             timestamp not null default current_timestamp on update current_timestamp,
        gr2o_opened_by          bigint unsigned not null,
        gr2o_changed            timestamp not null,
        gr2o_changed_by         bigint unsigned not null,
        gr2o_created            timestamp not null,
        gr2o_created_by         bigint unsigned not null,

        PRIMARY KEY (gr2o_patient_nr, gr2o_id_organization),
        UNIQUE KEY (gr2o_id_user, gr2o_id_organization),
        INDEX (gr2o_id_organization),
        INDEX (gr2o_opened),
        INDEX (gr2o_reception_code),
        INDEX (gr2o_opened_by),
        INDEX (gr2o_changed_by),
        INDEX (gr2o_consent)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

