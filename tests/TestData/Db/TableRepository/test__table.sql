-- This is a test tables description
CREATE TABLE if not exists test__table
(
    tt_id                   bigint unsigned not null auto_increment,
    tt_description          varchar(255) CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci null default null,

    PRIMARY KEY (tt_id)
    )
    ENGINE = InnoDB
    auto_increment = 0
    CHARACTER SET 'utf8mb4'
    COLLATE utf8mb4_general_ci;
