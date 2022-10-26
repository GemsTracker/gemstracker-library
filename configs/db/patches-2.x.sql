ALTER TABLE `gems__user_logins`
    ADD `gul_session_key` varchar(32) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `gul_can_login`;

CREATE TABLE `gems__password_reset_attempts`
(
    `gpra_id` bigint unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `gpra_id_organization` bigint unsigned NOT NULL,
    `gpra_ip_address` varchar(45) NOT NULL,
    `gpra_attempt_at` timestamp NULL
);
ALTER TABLE `gems__password_reset_attempts`
    ADD FOREIGN KEY (`gpra_id_organization`) REFERENCES `gems__organizations` (`gor_id_organization`) ON DELETE CASCADE;
