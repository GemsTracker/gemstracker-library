

CREATE TABLE if not exists gems__organizations (
        gor_id_organization bigint unsigned not null auto_increment,

        gor_name            varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gor_code            varchar(20)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_location        varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_url             varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_task            varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gor_contact_name    varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_contact_email   varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_welcome         text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        gor_signature       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        gor_style           varchar(15)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems',
        gor_iso_lang        char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
                            not null default 'en' references gems__languages (gml_iso_lang),

        gor_active          boolean not null default 1,

        gor_changed         timestamp not null default current_timestamp on update current_timestamp,
        gor_changed_by      bigint unsigned not null,
        gor_created         timestamp not null,
        gor_created_by      bigint unsigned not null,

        PRIMARY KEY(gor_id_organization)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- Default group
INSERT ignore INTO gems__organizations
    (gor_id_organization, gor_name, gor_location, gor_active, gor_iso_lang, gor_changed, gor_changed_by, gor_created, gor_created_by)
    VALUES
    (70, 'Erasmus MGZ', 'Rotterdam', 1, 'nl', current_timestamp, 0, current_timestamp, 0);

