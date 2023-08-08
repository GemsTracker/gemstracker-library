
CREATE TABLE if not exists gems__token_attempts (
        gta_id_attempt      bigint unsigned not null auto_increment,
        gta_id_token        varchar(9) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        gta_ip_address      varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
        gta_datetime        timestamp not null default current_timestamp,
        gta_activated       boolean null default 0,


        PRIMARY KEY (gta_id_attempt)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 10000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

