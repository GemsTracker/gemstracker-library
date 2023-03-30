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

ALTER TABLE `gems__organizations`
    ADD `gor_reset_tfa_template` bigint unsigned NULL AFTER `gor_reset_pass_template`;

INSERT INTO `gems__comm_templates` (`gct_name`, `gct_target`, `gct_code`, `gct_changed`, `gct_changed_by`, `gct_created`, `gct_created_by`)
VALUES ('Global TFA reset', 'staffPassword', 'tfaReset', now(), '0', now(), '0');

INSERT INTO `gems__comm_template_translations` (`gctt_id_template`, `gctt_lang`, `gctt_subject`, `gctt_body`) VALUES
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'tfaReset'), 'en', 'Your TFA has been reset', 'Your Authenticator TFA has been reset for the [b]{{organization}}[/b] site [b]{{project}}[/b]. Next time you log in, you will need to verify your login using SMS TFA, after which you can reactivate Authenticator TFA.'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'tfaReset'), 'nl', 'Je TFA is gereset', 'Je Authenticator TFA voor [b]{{organization}}[/b] site [b]{{project}}[/b] is zojuist gewist. De volgende keer dat je inlogt zul je met SMS TFA inloggen, waarna je weer Authenticator TFA kunt instellen.');

ALTER TABLE `gems__organizations`
    ADD `gor_confirm_change_email_template` bigint unsigned NULL AFTER `gor_reset_tfa_template`,
    ADD `gor_confirm_change_phone_template` bigint unsigned NULL AFTER `gor_confirm_change_email_template`;

INSERT INTO `gems__comm_templates` (`gct_name`, `gct_target`, `gct_code`, `gct_changed`, `gct_changed_by`, `gct_created`, `gct_created_by`)
VALUES
    ('Staff change email confirmation', 'staffPassword', 'confirmChangeEmail', now(), '0', now(), '0'),
    ('Staff change phone confirmation', 'staffPassword', 'confirmChangePhone', now(), '0', now(), '0');

INSERT INTO `gems__comm_template_translations` (`gctt_id_template`, `gctt_lang`, `gctt_subject`, `gctt_body`) VALUES
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangeEmail'), 'en', 'The confirmation code for your e-mail change', 'Please use the following code to confirm your e-mail change for {{organization}} site {{project}}: {{confirmation_code}}'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangeEmail'), 'nl', 'De bevestigingscode voor je email wijziging', 'Gebruik de volgende code om de email wijziging voor de {{organization}} site {{project}} te bevestigen: {{confirmation_code}}'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangePhone'), 'en', 'Your confirmation code', 'Verify your new phone number using this code: {{confirmation_code}}'),
    ((SELECT gct_id_template FROM gems__comm_templates WHERE gct_code = 'confirmChangePhone'), 'nl', 'Je bevestigingscode', 'Bevestig je nieuwe telefoonnummer met deze code: {{confirmation_code}}');

-- PATCH: Add id to mailservers
ALTER TABLE `gems__mail_servers`
    ADD `ggp_code` bigint unsigned not null auto_increment FIRST
    ADD PRIMARY KEY `gms_id_server` (`gms_id_server`),
    ADD UNIQUE `gms_from` (`gms_from`),
    DROP INDEX `PRIMARY`,
    DROP INDEX `gms_id_server`;
