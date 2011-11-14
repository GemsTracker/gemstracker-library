
-- Table containing the project staff
--
CREATE TABLE if not exists gems__staff (
        gsf_id_user          bigint unsigned not null references gems__user_ids (gui_id_user),

        gsf_login            varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gsf_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gsf_active           boolean null default 1,

        -- depreciated
        gsf_password         varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
    	gsf_failed_logins    int(11) unsigned null default 0,
        gsf_last_failed      timestamp null,
        -- end depreciated


        gsf_id_primary_group bigint unsigned
                               references gems__groups (ggp_id_group),
        gsf_iso_lang         char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
                               not null default 'nl' references gems__languages (gml_iso_lang),
        gsf_logout_on_survey boolean not null default 0,

        gsf_email            varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
                               unique key,

        gsf_first_name       varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsf_surname_prefix   varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsf_last_name        varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gsf_gender           char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
                               not null default 'U',
        -- gsf_birthday         date,
        -- gsf_function         varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- gsf_address_1        varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_address_2        varchar(80) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_zipcode          varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_city             varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_region           varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_iso_country      char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
        --                          references phpwcms__phpwcms_country (country_iso),
        gsf_phone_1          varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_phone_2          varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- gsf_phone_3          varchar(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- depreciated
        gsf_reset_key        varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gsf_reset_requested  timestamp null,
        -- end depreciated

        gsf_changed          timestamp not null default current_timestamp on update current_timestamp,
        gsf_changed_by       bigint unsigned not null,
        gsf_created          timestamp not null,
        gsf_created_by       bigint unsigned not null,

        PRIMARY KEY(gsf_id_user),
        UNIQUE KEY(gsf_login, gsf_id_organization)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 2001
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

