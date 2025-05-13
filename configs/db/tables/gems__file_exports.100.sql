CREATE TABLE if not exists gems__file_exports (
    gfex_id                 bigint unsigned not null auto_increment,

    gfex_export_id          varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
    gfex_id_user            bigint unsigned null default null references gems__staff (gsf_id_user),
    gfex_file_name          varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
    gfex_export_type        varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
    gfex_export_settings    json null,
    gfex_column_order       json not null,

    gfex_data               json not null,
    gfex_order              mediumint unsigned not null,
    gfex_row_count          int unsigned not null default 0,

    gfex_created            timestamp not null default current_timestamp,

    PRIMARY KEY (gfex_id)
)
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
