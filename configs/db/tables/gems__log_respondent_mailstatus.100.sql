CREATE TABLE if not exists gems__log_respondent_mailstatus (
        glrm_id                 bigint unsigned not null auto_increment,

        glrm_id_user            bigint unsigned not null references gems__respondents (grs_id_user),
        glrm_id_organization    bigint unsigned not null references gems__organizations (gor_id_organization),

        glrm_mailable_field     varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'
                                not null default 'gr2o_mailable',
        glrm_old_mailable       tinyint null default null references gems__mail_codes (gmc_id),
        glrm_new_mailable       tinyint null default null references gems__mail_codes (gmc_id),

        glrm_comment            text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,

        glrm_created            timestamp not null default current_timestamp,
        glrm_created_by         bigint unsigned not null,

        PRIMARY KEY (glrm_id)
    )
    ENGINE=InnoDB
    auto_increment = 2000000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

