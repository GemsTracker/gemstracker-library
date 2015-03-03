
CREATE TABLE IF NOT EXISTS gems__respondent_relations (
        grr_id                      bigint(20) NOT NULL AUTO_INCREMENT,
        grr_id_respondent           bigint(20) NOT NULL,
        grr_type                    varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',

        -- When staff this holds the id
        grr_id_staff                bigint(20) NULL DEFAULT NULL,

        -- when not staff, we need at least name, gender and email
        grr_email                   varchar(100) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        -- grs_initials_name           varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grr_first_name              varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_surname_prefix          varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grr_last_name               varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_partner_surname_prefix  varchar(10) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        -- grs_partner_last_name       varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci',
        grr_gender                  char(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'U',
        grr_birthdate               date NULL DEFAULT NULL,
        grr_comments                text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        grr_active                  boolean not null default 1,

        grr_changed                 timestamp not null default current_timestamp on update current_timestamp,
        grr_changed_by              bigint unsigned not null,
        grr_created                 timestamp not null,
        grr_created_by              bigint unsigned not null,

        PRIMARY KEY (grr_id),
        KEY grr_id_respondent (grr_id_respondent,grr_id_staff)
    )
    ENGINE=InnoDB
    DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci
    AUTO_INCREMENT = 10001;