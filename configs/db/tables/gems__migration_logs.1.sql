-- Log of all table, seed and patch changes
CREATE TABLE if not exists gems__migration_logs (
    gml_id_migration    bigint unsigned not null auto_increment,
    gml_name            varchar(255) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci not null,
    gml_type            varchar(30) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci not null,
    gml_version         varchar(30) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci not null,
    gml_module          varchar(30) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci not null,

    gml_status          varchar(30) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci not null,
    gml_duration        float null,
    gml_sql             longtext CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci not null,
    gml_comment         text CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci null,
    gml_created         timestamp not null default current_timestamp,
    PRIMARY KEY (gml_id_migration)
)
ENGINE=InnoDB
AUTO_INCREMENT = 1
CHARACTER SET 'utf8mb4' COLLATE utf8mb4_unicode_ci;