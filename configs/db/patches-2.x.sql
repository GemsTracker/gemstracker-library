ALTER TABLE `gems__user_logins`
    ADD `gul_session_key` varchar(32) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `gul_can_login`;
