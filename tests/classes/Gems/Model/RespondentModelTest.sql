CREATE TABLE gems__respondent2org (
        gr2o_patient_nr         varchar(15) not null,
        gr2o_id_organization    INTEGER not null,

        gr2o_id_user            INTEGER not null,

        -- gr2o_id_physician       INTEGER,

        -- gr2o_treatment          varchar(200),
        gr2o_email              varchar(100),
        gr2o_mailable           TINYINT(1) not null default 1,
        gr2o_comments           text,

        gr2o_consent            varchar(20) not null default 'Unknown',
        gr2o_reception_code     varchar(20) default 'OK' not null,

        gr2o_opened             TEXT not null default current_timestamp,
        gr2o_opened_by          INTEGER not null,
        gr2o_changed            TEXT not null,
        gr2o_changed_by         INTEGER not null,
        gr2o_created            TEXT not null,
        gr2o_created_by         INTEGER not null,

        PRIMARY KEY (gr2o_patient_nr, gr2o_id_organization),
        UNIQUE (gr2o_id_user, gr2o_id_organization)
    )
    ;

CREATE TABLE gems__respondents (
        grs_id_user                INTEGER not null,

        grs_ssn                    varchar(128) unique,

        grs_iso_lang               char(2) not null default 'nl',

        -- grs_email                  varchar(100),

        -- grs_initials_name          varchar(30) ,
        grs_first_name             varchar(30) ,
        -- grs_surname_prefix         varchar(10) ,
        grs_last_name              varchar(50) ,
        -- grs_partner_surname_prefix varchar(10) ,
        -- grs_partner_last_name      varchar(50) ,
        grs_gender                 char(1) not null default 'U',
        grs_birthday               TEXT,

        grs_address_1              varchar(80) ,
        grs_address_2              varchar(80) ,
        grs_zipcode                varchar(10) ,
        grs_city                   varchar(40) ,
        -- grs_region                 varchar(40) ,
        grs_iso_country            char(2) not null default 'NL',
        grs_phone_1                varchar(25) ,
        grs_phone_2                varchar(25) ,
        -- grs_phone_3                varchar(25) ,
        -- grs_phone_4                varchar(25) ,

        grs_changed                TEXT not null default current_timestamp,
        grs_changed_by             INTEGER not null,
        grs_created                TEXT not null,
        grs_created_by             INTEGER not null,

        PRIMARY KEY(grs_id_user)
    )
    ;

CREATE TABLE gems__reception_codes (
      grc_id_reception_code varchar(20) not null,
      grc_description       varchar(40) not null,

      grc_success           TINYINT(1) not null default 0,

      grc_for_surveys       tinyint not null default 0,
      grc_redo_survey       tinyint not null default 0,
      grc_for_tracks        TINYINT(1) not null default 0,
      grc_for_respondents   TINYINT(1) not null default 0,
      grc_overwrite_answers TINYINT(1) not null default 0,
      grc_active            TINYINT(1) not null default 1,

      grc_changed    TEXT not null default current_timestamp,
      grc_changed_by INTEGER not null,
      grc_created    TEXT not null,
      grc_created_by INTEGER not null,

      PRIMARY KEY (grc_id_reception_code)
   )
   ;

INSERT INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
    ('OK', '', 1, 1, 0, 1, 1, 0, 1, '2017-08-30 12:00:00', 1, '2017-08-30 12:00:00', 1),
    ('redo', 'Redo survey', 0, 1, 2, 0, 0, 1, 1, '2017-08-30 12:00:00', 1, '2017-08-30 12:00:00', 1),
    ('refused', 'Survey refused', 0, 1, 0, 0, 0, 0, 1, '2017-08-30 12:00:00', 1, '2017-08-30 12:00:00', 1),
    ('retract', 'Consent retracted', 0, 0, 0, 1, 1, 1, 1, '2017-08-30 12:00:00', 1, '2017-08-30 12:00:00', 1),
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 1, 0, '2017-08-30 12:00:00', 1, '2017-08-30 12:00:00', 1),
    ('stop', 'Stopped participating', 0, 2, 0, 1, 1, 0, 1, '2017-08-30 12:00:00', 1, '2017-08-30 12:00:00', 1);

CREATE TABLE gems__organizations (
        gor_id_organization         INTEGER not null ,

        gor_name                    varchar(50)   not null,
        gor_code                    varchar(20),
        gor_user_class              varchar(30)   not null default 'StaffUser',
        gor_location                varchar(255),
        gor_url                     varchar(127),
        gor_url_base                varchar(1270),
        gor_task                    varchar(50),

        gor_provider_id             varchar(10),

        -- A : separated list of organization numbers that can look at respondents in this organization
        gor_accessible_by           text,

        gor_contact_name            varchar(50),
        gor_contact_email           varchar(127),
        gor_welcome                 text,
        gor_signature               text,

        gor_respondent_edit         varchar(255),
        gor_respondent_show         varchar(255),
        gor_token_ask               varchar(255),

        gor_style                   varchar(15)  not null default 'gems',
        gor_resp_change_event       varchar(128) ,
        gor_iso_lang                char(2) not null default 'en',

        gor_has_login               TINYINT(1) not null default 1,
        gor_has_respondents         TINYINT(1) not null default 0,
        gor_add_respondents         TINYINT(1) not null default 1,
        gor_respondent_group        INTEGER,
        gor_create_account_template INTEGER,
        gor_reset_pass_template     INTEGER,
        gor_allowed_ip_ranges       text,
        gor_active                  TINYINT(1) not null default 1,

        gor_changed                 TEXT not null default current_timestamp,
        gor_changed_by              INTEGER not null,
        gor_created                 TEXT not null,
        gor_created_by              INTEGER not null,

        PRIMARY KEY(gor_id_organization)
    )
    ;