
-- Support table for generating unique staff/respondent id's
--
CREATE TABLE if not exists gems__user_ids (
        gui_id_user          bigint unsigned not null,

        gui_created          timestamp not null  default current_timestamp,

        PRIMARY KEY (gui_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
