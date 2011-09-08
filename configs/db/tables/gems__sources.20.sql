
CREATE TABLE if not exists gems__sources (
        gso_id_source int unsigned not null auto_increment,
        gso_source_name varchar(40) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' unique key not null,

        gso_ls_url varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' unique key not null,
        gso_ls_class varchar(60) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'Gems_Source_LimeSurvey1m8Database',
        gso_ls_adapter varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_ls_dbhost varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_ls_database varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_ls_table_prefix varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_ls_username varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_ls_password varchar(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gso_active boolean not null default 1, 

        gso_status varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gso_last_synch timestamp null, 

        gso_changed timestamp not null default current_timestamp on update current_timestamp,
        gso_changed_by bigint unsigned not null,
        gso_created timestamp not null,
        gso_created_by bigint unsigned not null,

        PRIMARY KEY(gso_id_source)
    )
    ENGINE=InnoDB
    auto_increment = 60
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

