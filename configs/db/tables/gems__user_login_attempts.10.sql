
-- Table for keeping track of failed login attempts
--
CREATE TABLE if not exists gems__user_login_attempts (
        gula_login            varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        gula_id_organization  bigint not null,

    	gula_failed_logins    int(11) unsigned not null default 0,
        gula_last_failed      timestamp null,
        gula_block_until      timestamp null,

        PRIMARY KEY (gula_login, gula_id_organization)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
