
CREATE TABLE if not exists gems__survey_question_options (
        gsqo_id_survey      int unsigned not null references gems__surveys (gsu_id_survey),
        gsqo_name           varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        -- Order is key as you never now what is in the key used by the providing system
        gsqo_order          int unsigned not null default 0,

        gsqo_key            varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',
        gsqo_label          varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci',

        gsqo_changed        timestamp not null default current_timestamp on update current_timestamp,
        gsqo_changed_by     bigint unsigned not null,
        gsqo_created        timestamp not null,
        gsqo_created_by     bigint unsigned not null,

        PRIMARY KEY (gsqo_id_survey, gsqo_name, gsqo_order)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';
