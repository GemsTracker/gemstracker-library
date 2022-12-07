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

ALTER TABLE `gems__groups`
    ADD `ggp_code` varchar(30) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `ggp_id_group`,
    ADD `ggp_member_type` varchar(30) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `ggp_respondent_members`;

UPDATE gems__groups SET ggp_member_type = 'staff' WHERE ggp_staff_members = 1 AND ggp_respondent_members = 0;
UPDATE gems__groups SET ggp_member_type = 'respondent' WHERE ggp_staff_members = 0 AND ggp_respondent_members = 1;

UPDATE gems__groups SET ggp_code = REGEXP_REPLACE(LOWER(ggp_name), '[^a-z_]', '_');
ALTER TABLE `gems__groups` ADD UNIQUE `ggp_code` (`ggp_code`);
