
CREATE TABLE if not exists gems__mail_servers (
        gms_id_server  bigint unsigned not null auto_increment,
        gms_from       varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,

        gms_server     varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        gms_port       smallint unsigned not null default 25,
        gms_ssl        tinyint not null default 0,
        gms_user       varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,
        gms_password   varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,

        gms_changed    timestamp not null default current_timestamp on update current_timestamp,
        gms_changed_by bigint unsigned not null,
        gms_created    timestamp not null default current_timestamp,
        gms_created_by bigint unsigned not null,

        PRIMARY KEY (gms_id_server),
        UNIQUE INDEX (gms_from)

    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 20
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

