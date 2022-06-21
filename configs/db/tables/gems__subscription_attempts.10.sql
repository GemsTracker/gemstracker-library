
CREATE TABLE if not exists gems__subscription_attempts (
        gsa_id_attempt      bigint unsigned not null auto_increment,
        gsa_type_attempt    varchar(16) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        gsa_ip_address      varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null,
        gsa_datetime        timestamp not null default current_timestamp,
        gsa_activated       boolean null default 0,


        PRIMARY KEY (gsa_id_attempt)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 10000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

