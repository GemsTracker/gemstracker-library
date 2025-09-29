CREATE TABLE if not exists gems__batch (
    gba_id                 char(36) not null,
    gba_iteration          int unsigned not null,

    gba_name               varchar(64) null,
    gba_group              varchar(64) null,
    gba_status             varchar(64) not null,
    gba_synchronous        tinyint(64) not null default 0,

    gba_message            JSON null,
    gba_message_class      varchar(128) null,

    gba_info                text null,

    gba_finished           timestamp not null default current_timestamp,
    gba_created            timestamp not null default current_timestamp,
    gba_changed            timestamp not null default current_timestamp ON UPDATE current_timestamp,

    PRIMARY KEY (gba_id, gba_iteration)
)
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
