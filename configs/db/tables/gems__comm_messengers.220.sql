
CREATE TABLE gems__comm_messengers (
    gcm_id_messenger            bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gcm_id_order                int unsigned NOT NULL,
    gcm_type                    varchar(32) COLLATE 'utf8mb4_general_ci' NOT NULL,
    gcm_name                    varchar(32) COLLATE 'utf8mb4_general_ci' NOT NULL,
    gcm_description             varchar(255) COLLATE 'utf8mb4_general_ci' NULL,

    gcm_messenger_identifier    varchar(32) COLLATE 'utf8mb4_general_ci' NULL,
    gcm_active                  tinyint unsigned NOT NULL DEFAULT '1',

    gcm_changed                 timestamp not null default current_timestamp on update current_timestamp,
    gcm_changed_by              bigint unsigned not null,
    gcm_created                 timestamp not null default current_timestamp,
    gcm_created_by              bigint unsigned not null,

    INDEX (gcm_name)
)
ENGINE=InnoDB
AUTO_INCREMENT = 1300
CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
