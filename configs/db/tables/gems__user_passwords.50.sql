
-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__user_passwords (
        gup_id_user          bigint unsigned not null references gems__user_logins (gul_id_user),

        gup_password         varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,
        gup_reset_key        char(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci' null,
        gup_reset_requested  timestamp null,
        gup_reset_required   boolean not null default 0,
        gup_last_pwd_change  timestamp not null default 0,  -- Can only have on current_timestamp so default to 0

        gup_changed          timestamp not null default current_timestamp on update current_timestamp,
        gup_changed_by       bigint unsigned not null,
        gup_created          timestamp not null,
        gup_created_by       bigint unsigned not null,

        PRIMARY KEY (gup_id_user),
        UNIQUE KEY (gup_reset_key)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8_unicode_ci';
