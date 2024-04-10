
CREATE TABLE if not exists gems__groups (
        ggp_id_group              bigint unsigned not null auto_increment,
        ggp_code                  varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        ggp_name                  varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        ggp_description           varchar(50) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,

        ggp_role                  varchar(150) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null default 'respondent',
        -- The ggp_role value(s) determines someones roles as set in the bootstrap

        ggp_may_set_groups        varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,
        ggp_default_group         bigint unsigned null,

        ggp_group_active          boolean not null default 1,
        ggp_staff_members         boolean not null default 0,
        ggp_respondent_members    boolean not null default 1,
        ggp_member_type           varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        ggp_allowed_ip_ranges     text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,
        ggp_no_2factor_ip_ranges  text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,
        ggp_2factor_set           tinyint not null default 50,
        ggp_2factor_not_set       tinyint not null default 0,

        ggp_respondent_browse     varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,
        ggp_respondent_edit       varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,
        ggp_respondent_show       varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,

        ggp_mask_settings         text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null default null,

        ggp_changed               timestamp not null default current_timestamp on update current_timestamp,
        ggp_changed_by            bigint unsigned not null,
        ggp_created               timestamp not null default current_timestamp,
        ggp_created_by            bigint unsigned not null,

        PRIMARY KEY(ggp_id_group),
        UNIQUE KEY ggp_code (ggp_code)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 900
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
