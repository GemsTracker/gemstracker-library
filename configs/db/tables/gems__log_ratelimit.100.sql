
CREATE TABLE if not exists gems__log_ratelimit (
        glr_id              int unsigned not null auto_increment,

        glr_ip              varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,
        glr_identity        varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci',
        glr_method          varchar(10) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,
        glr_route           varchar(250) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' not null,
        glr_max_requests    int unsigned not null default 0,
        glr_time_sec        int unsigned not null default 0,

        glr_created         timestamp not null default current_timestamp,

        PRIMARY KEY (glr_id),
        INDEX (glr_ip),
        INDEX (glr_identity),
        INDEX (glr_route)
    )
    ENGINE=InnoDB
    auto_increment = 0
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
