
CREATE TABLE if not exists gems__conditions (
        gcon_id                  bigint unsigned not null auto_increment,

        gcon_type                varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        gcon_class               varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        gcon_name                varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,

        -- Generic text fields so the classes can fill them as they please
        gcon_condition_text1        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,
        gcon_condition_text2        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,
        gcon_condition_text3        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,
        gcon_condition_text4        varchar(200) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,

        gcon_active              boolean not null default 1,

        gcon_changed             timestamp not null default current_timestamp on update current_timestamp,
        gcon_changed_by          bigint unsigned not null,
        gcon_created             timestamp not null default '0000-00-00 00:00:00',
        gcon_created_by          bigint unsigned not null,

        PRIMARY KEY (gcon_id)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 1000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

