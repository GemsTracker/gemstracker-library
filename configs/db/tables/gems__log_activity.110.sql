
CREATE TABLE if not exists gems__log_activity (
        gla_id              bigint unsigned not null auto_increment,

        gla_action          int unsigned    not null references gems__log_setup     (gls_id_action),
        gla_respondent_id   bigint unsigned null     references gems__respondents   (grs_id_user),

        gla_by              bigint unsigned null,
        gla_organization    bigint unsigned not null, -- references gems__organizations (gor_id_organization),
        gla_role            varchar(20) character set 'utf8mb4' collate 'utf8mb4_unicode_ci' not null,

        gla_changed         boolean not null default 0,
        gla_message         text character set 'utf8mb4' collate 'utf8mb4_unicode_ci' null default null,
        gla_data            text character set 'utf8mb4' collate 'utf8mb4_unicode_ci' null default null,
        gla_method          varchar(10) character set 'utf8mb4' collate 'utf8mb4_unicode_ci' not null,
        gla_remote_ip       varchar(64) character set 'utf8mb4' collate 'utf8mb4_unicode_ci' not null,

        gla_created         timestamp not null default current_timestamp,

        PRIMARY KEY (gla_id),
        INDEX (gla_action),
        INDEX (gla_respondent_id),
        INDEX (gla_by),
        INDEX (gla_organization),
        INDEX (gla_role),
        INDEX (gla_created)
   )
   ENGINE=InnoDB
   auto_increment = 100000
   CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
