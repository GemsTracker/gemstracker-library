

CREATE TABLE if not exists gems__mail_codes (
        gmc_id                      tinyint not null,
        gmc_mail_to_target          varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        gmc_mail_cause_target       varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,

        gmc_code                    varchar(40) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,

        gmc_for_surveys             boolean not null default 1,
        gmc_for_tracks              boolean not null default 1,
        gmc_for_respondents         boolean not null default 1,
        gmc_active                  boolean not null default 1,

        gmc_changed                 timestamp not null default current_timestamp on update current_timestamp,
        gmc_changed_by              bigint unsigned not null,
        gmc_created                 timestamp not null,
        gmc_created_by              bigint unsigned not null,

        PRIMARY KEY (gmc_id)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

INSERT INTO gems__mail_codes
    (gmc_id, gmc_mail_to_target, gmc_mail_cause_target, gmc_for_surveys, gmc_changed, gmc_changed_by, gmc_created, gmc_created_by)
VALUES
    (0, 'No', 'Never mail', 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (100, 'Yes', 'Mail', 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
