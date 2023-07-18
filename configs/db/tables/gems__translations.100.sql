
CREATE TABLE `gems__translations` (
      gtrs_id               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      gtrs_table            varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' NOT NULL,
      gtrs_field            varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' NOT NULL,
      gtrs_keys             varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' NOT NULL,
      gtrs_iso_lang         varchar(6) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' NOT NULL,
      gtrs_translation      text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci' NULL,
      gtrs_changed          timestamp NOT NULL DEFAULT current_timestamp ON UPDATE current_timestamp,
      gtrs_changed_by       bigint(20) unsigned NOT NULL,
      gtrs_created          timestamp NOT NULL DEFAULT current_timestamp,
      gtrs_created_by       bigint(20) unsigned NOT NULL,

      PRIMARY KEY (gtrs_id),
      INDEX gtrs_table (gtrs_table),
      INDEX gtrs_field (gtrs_field),
      INDEX gtrs_keys (gtrs_keys),
      INDEX gtrs_iso_lang (gtrs_iso_lang)
    )
    ENGINE=InnoDB
    auto_increment = 100000
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_general_ci';
