
CREATE TABLE if not exists gems__masks
(
    gm_id                   bigint unsigned not null auto_increment,
    gm_id_order             int not null default 10,
    gm_description          varchar(255) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci null default null,

    gm_group                bigint unsigned null references gems__groups (ggp_id_group),
    gm_id_organization      bigint unsigned null references gems__organizations (gor_id_organization),
            
    gm_mask_settings        text CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci null,

    gm_changed              timestamp       not null default current_timestamp on update current_timestamp,
    gm_changed_by           bigint unsigned not null,
    gm_created              timestamp       not null default current_timestamp,
    gm_created_by           bigint unsigned not null,

    PRIMARY KEY (gm_id)
)
    ENGINE = InnoDB
    auto_increment = 20
    CHARACTER SET 'utf8mb4'
    COLLATE utf8mb4_general_ci;
