
CREATE TABLE if not exists gems__log_setup (
        gls_id_action       int unsigned not null auto_increment,
        gls_name            varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null unique,

        gls_when_no_user    boolean not null default 0,
        gls_on_action       boolean not null default 0,
        gls_on_post         boolean not null default 0,
        gls_on_change       boolean not null default 1,

        gls_changed         timestamp not null default current_timestamp on update current_timestamp,
        gls_changed_by      bigint unsigned not null,
        gls_created         timestamp not null default current_timestamp,
        gls_created_by      bigint unsigned not null,

        PRIMARY KEY (gls_id_action),
        INDEX (gls_name)
    )
    ENGINE=InnoDB
    auto_increment = 2000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
