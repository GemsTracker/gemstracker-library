
-- Table containing the password history of users
--
CREATE TABLE if not exists gems__user_password_history (
        guph_id               bigint unsigned not null auto_increment,
        guph_id_user          bigint unsigned not null references gems__user_logins (gul_id_user),

        guph_password         varchar(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,
        guph_set_time         timestamp not null default current_timestamp on update current_timestamp,

        PRIMARY KEY (guph_id),
        KEY (guph_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
