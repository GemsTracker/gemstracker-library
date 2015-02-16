
CREATE TABLE if not exists gems__log_actions (
        glac_id_action  int unsigned not null auto_increment,
        glac_name       varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null unique,

        glac_change     boolean not null default 1,
        glac_on_post    boolean not null default 1,
        glac_log        int(1) NOT NULL default 1,

        glac_changed    timestamp not null default current_timestamp on update current_timestamp,
        glac_changed_by bigint unsigned not null,
        glac_created    timestamp not null,
        glac_created_by bigint unsigned not null,

        PRIMARY KEY (glac_id_action)
    )
    ENGINE=InnoDB
    auto_increment = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

