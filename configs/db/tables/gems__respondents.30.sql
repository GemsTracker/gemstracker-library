
CREATE TABLE if not exists gems__respondents (
        grs_id_user                bigint unsigned not null references gems__user_ids (gui_id_user),

        grs_ssn                    varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null unique key,

        grs_iso_lang               char(2) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null default 'nl',

        -- grs_email                  varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,

        -- grs_initials_name          varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_first_name             varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_surname_prefix         varchar(10) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_last_name              varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        -- grs_partner_surname_prefix varchar(10) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        -- grs_partner_last_name      varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        -- grs_partner_name_after     boolean not null default 1,
        grs_gender                 char(1) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null default 'U',
        grs_birthday               date,

        grs_address_1              varchar(80) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_address_2              varchar(80) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_zipcode                varchar(10) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_city                   varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        -- grs_region                 varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_iso_country            char(2) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null default 'NL',
        grs_phone_1                varchar(25) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        grs_phone_2                varchar(25) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        -- grs_phone_3                varchar(25) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        -- grs_phone_4                varchar(25) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        grs_changed                timestamp not null default current_timestamp on update current_timestamp,
        grs_changed_by             bigint unsigned not null,
        grs_created                timestamp not null default current_timestamp,
        grs_created_by             bigint unsigned not null,

        PRIMARY KEY (grs_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

