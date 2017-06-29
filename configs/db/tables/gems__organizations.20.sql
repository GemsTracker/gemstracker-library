
CREATE TABLE if not exists gems__organizations (
        gor_id_organization         bigint unsigned not null auto_increment,

        gor_name                    varchar(50)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gor_code                    varchar(20)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_user_class              varchar(30)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'StaffUser',
        gor_location                varchar(255)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_url                     varchar(127)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_url_base                varchar(1270) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_task                    varchar(50)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_provider_id             varchar(10)   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- A : separated list of organization numbers that can look at respondents in this organization
        gor_accessible_by           text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_contact_name            varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_contact_email           varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_welcome                 text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  null,
        gor_signature               text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_respondent_edit         varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,
        gor_respondent_show         varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null default null,

        gor_style                   varchar(15)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems',
        gor_resp_change_event       varchar(128) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_iso_lang                char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'en',

        gor_has_login               boolean not null default 1,
        gor_has_respondents         boolean not null default 0,
        gor_add_respondents         boolean not null default 1,
        gor_respondent_group        bigint unsigned null references gems__groups (ggp_id_group),
        gor_create_account_template bigint unsigned null,
        gor_reset_pass_template     bigint unsigned null,
        gor_allowed_ip_ranges       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_active                  boolean not null default 1,

        gor_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gor_changed_by              bigint unsigned not null,
        gor_created                 timestamp not null,
        gor_created_by              bigint unsigned not null,

        PRIMARY KEY(gor_id_organization),
        KEY (gor_code)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT ignore INTO gems__organizations (gor_id_organization, gor_name, gor_changed, gor_changed_by, gor_created, gor_created_by)
    VALUES
    (70, 'New organization', CURRENT_TIMESTAMP, 0, CURRENT_TIMESTAMP, 0);
