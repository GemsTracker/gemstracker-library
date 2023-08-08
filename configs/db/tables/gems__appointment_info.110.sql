
CREATE TABLE if not exists gems__appointment_info (
    gai_id                  bigint unsigned not null auto_increment,

    gai_name               varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,

    gai_id_filter          bigint unsigned not null references gems__appointment_filters (gaf_id),

    gai_type               varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,

    -- Generic text fields so the classes can fill them as they please
    gai_field_key          varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
    gai_field_value        varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,

    gai_active              boolean not null default 1,

    gai_changed             timestamp not null default current_timestamp on update current_timestamp,
    gai_changed_by          bigint unsigned not null,
    gai_created             timestamp not null default current_timestamp,
    gai_created_by          bigint unsigned not null,

    PRIMARY KEY (gai_id)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
