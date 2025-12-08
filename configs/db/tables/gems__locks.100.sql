CREATE TABLE if not exists gems__locks (
    glock_key             varchar(255) not null,
    glock_is_locked       tinyint(1) not null default 0,
    glock_locked_until    datetime null,
    glock_changed         timestamp not null default current_timestamp on update current_timestamp,
    glock_changed_by      bigint unsigned not null,
    glock_created         timestamp not null default current_timestamp,
    glock_created_by      bigint unsigned not null,


   PRIMARY KEY (glock_key)
)
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';