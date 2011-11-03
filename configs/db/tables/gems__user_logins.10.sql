
-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__user_logins (
        gul_id_user          bigint unsigned not null auto_increment,

        gul_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gul_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gul_user_class       varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NoLogin',

        gul_changed          timestamp not null default current_timestamp on update current_timestamp,
        gul_changed_by       bigint unsigned not null,
        gul_created          timestamp not null,
        gul_created_by       bigint unsigned not null,

        PRIMARY KEY (gsl_id_user),
        UNIQUE (gsl_login, gsl_id_organization)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 10001
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
