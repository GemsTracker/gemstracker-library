
-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__user_logins (
        gul_id_user          bigint unsigned not null auto_increment,

        gul_login            varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null references gems__staff (gsf_login),
        gul_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gul_user_class       varchar(30) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' not null default 'NoLogin',
        gul_can_login        boolean not null default 0,

        gul_two_factor_key   varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null default null,
        gul_enable_2factor   boolean not null default 1,

        gul_otp_count        bigint unsigned NOT NULL DEFAULT 0,
        gul_otp_requested    timestamp NULL,

        gul_changed          timestamp not null default current_timestamp on update current_timestamp,
        gul_changed_by       bigint unsigned not null,
        gul_created          timestamp not null,
        gul_created_by       bigint unsigned not null,

        PRIMARY KEY (gul_id_user),
        UNIQUE (gul_login, gul_id_organization)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 10001
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';

/*
-- Code to restore login codes after failed update. You just never know when we might need it again.

UPDATE gems__user_logins
    SET gul_user_class =
    CASE
        WHEN EXISTS(SELECT gsf_id_user FROM gems__staff WHERE gsf_login = gul_login AND gsf_id_organization = gul_id_organization) THEN
            CASE
                WHEN EXISTS(SELECT gup_id_user FROM gems__user_passwords WHERE gup_id_user = gul_id_user) THEN 'StaffUser'
                ELSE 'OldStaffUser'
            END
        WHEN EXISTS(SELECT gr2o_id_user FROM gems__respondent2org WHERE gr2o_patient_nr = gul_login AND gr2o_id_organization = gul_id_organization) THEN 'RespondentUser'
        ELSE 'NoLogin'
    END
    WHERE gul_user_class = 'StaffUser';

*/
