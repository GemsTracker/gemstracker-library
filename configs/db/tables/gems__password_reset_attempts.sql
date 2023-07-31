
CREATE TABLE if not exists gems__password_reset_attempts
(
    gpra_id                   bigint unsigned not null auto_increment,
    gpra_id_organization      bigint unsigned not null references gems__organizations (gor_id_organization),
    gpra_ip_address           varchar(255) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci null default null,
    gpra_attempt_at           timestamp not null default current_timestamp,

    PRIMARY KEY (gpra_id)
)
    ENGINE = InnoDB
    CHARACTER SET 'utf8mb4'
    COLLATE utf8mb4_general_ci;
