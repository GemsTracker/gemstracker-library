
-- Table containing the users that are allowed to login
--
CREATE TABLE if not exists gems__user_ids (
        gui_id_user          bigint unsigned not null,

        gui_created          timestamp not null,

        PRIMARY KEY (gui_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
