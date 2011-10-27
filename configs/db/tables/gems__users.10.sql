
-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__users (
        gsu_id_user          bigint unsigned not null,
        gsu_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gsu_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        gsu_user_class       varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gsu_active           boolean not null default 1,

        -- Common fields for standard 'store password in Gems' logins
        -- Not every gsu_user_class will use them
        gsu_password         varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
    	gsu_failed_logins    int(11) unsigned not null default 0,
        gsu_last_failed      timestamp null,
        gsu_reset_key        varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gsu_reset_requested  timestamp null,
        gsu_reset_required   boolean not null default 0,

        gsu_changed          timestamp not null default current_timestamp on update current_timestamp,
        gsu_changed_by       bigint unsigned not null,
        gsu_created          timestamp not null,
        gsu_created_by       bigint unsigned not null,

        PRIMARY KEY (gsu_id_user, gsu_id_organization),
        UNIQUE (gsu_login, gsu_id_organization)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
