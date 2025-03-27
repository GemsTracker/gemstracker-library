
CREATE TABLE if not exists gems__app_versions (
        gav_id_version      int unsigned not null auto_increment,
        gav_app_version     varchar(100) null default null,

        gav_created         timestamp not null default current_timestamp,

        PRIMARY KEY (gav_id_version),
        UNIQUE KEY (gav_app_version)
    )
    ENGINE=InnoDB
    auto_increment = 1
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
