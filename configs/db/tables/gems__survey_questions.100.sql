
CREATE TABLE if not exists gems__survey_questions (
        gsq_id_survey       bigint unsigned not null references gems__surveys (gsu_id_survey),
        gsq_name            varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,

        gsq_name_parent     varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        gsq_order           int unsigned not null default 10,
        gsq_type            smallint unsigned not null default 1,
        gsq_class           varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        gsq_group           varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gsq_label           text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',
        gsq_description     text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci',

        gsq_changed         timestamp not null default current_timestamp on update current_timestamp,
        gsq_changed_by      bigint unsigned not null,
        gsq_created         timestamp not null default current_timestamp,
        gsq_created_by      bigint unsigned not null,

        PRIMARY KEY (gsq_id_survey, gsq_name)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
