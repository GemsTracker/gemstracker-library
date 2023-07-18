
CREATE TABLE if not exists gems__appointment_filters (
        gaf_id                  bigint unsigned not null auto_increment,
        gaf_class               varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,

        gaf_manual_name         varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
        gaf_calc_name           varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,

        gaf_id_order            int not null default 10,

        -- Generic text fields so the classes can fill them as they please
        gaf_filter_text1        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
        gaf_filter_text2        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
        gaf_filter_text3        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,
        gaf_filter_text4        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' null,

        gaf_active              boolean not null default 1,

        gaf_changed             timestamp not null default current_timestamp on update current_timestamp,
        gaf_changed_by          bigint unsigned not null,
        gaf_created             timestamp not null default current_timestamp,
        gaf_created_by          bigint unsigned not null,

        PRIMARY KEY (gaf_id)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
