
CREATE TABLE if not exists gems__staff2groups (
        gs2g_id_user bigint unsigned not null references gems__staff (gsf_id_user),
        gs2g_id_group bigint unsigned not null references gems__groups (ggp_id_group),

        gs2g_active boolean not null default 1,

        gs2g_changed timestamp not null default current_timestamp on update current_timestamp,
        gs2g_changed_by bigint unsigned not null,
        gs2g_created timestamp not null,
        gs2g_created_by bigint unsigned not null,

        PRIMARY KEY (gs2g_id_user, gs2g_id_group)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';


