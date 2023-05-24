CREATE TABLE if not exists gems__migration_logs (
    gml_id_migration    bigint unsigned not null auto_increment,
    gml_name            varchar(255) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci not null,
    gml_type            varchar(30) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci not null,
    gml_status          varchar(30) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci not null,
    gml_sql             text CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci not null,
    gml_comment         text CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci not null,
    gml_created         timestamp not null default current_timestamp,
    PRIMARY KEY (gml_id_migration)
)
ENGINE=InnoDB
AUTO_INCREMENT = 1
CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
